<?php

include "vendor/autoload.php";

$loop = React\EventLoop\Factory::create();

$config = json_decode(file_get_contents("/app/config.json"), true);


$worker = $config['workers'];
$nginx = $config['nginx'];
$git = $config['git'];

$process_list = array();
$routine_process = array();

//Create new instance of Worker
function createProcess(int $index)
{

    $config = json_decode(file_get_contents("/app/config.json"), true);


    $worker = $config['workers'];
    $nginx = $config['nginx'];

    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("file", "/tmp/worker_error$index.txt", "a"),
    );

    $cwd = '/tmp';

    unlink($worker['socket_dir']."socket_worker".$index."_reactphp.sock");

    $command = "php " . $config['project_dir'] . $worker['file'] . " --env=prd --mode=http --worker $index";
    echo $command . PHP_EOL;

    $process = proc_open($command, $descriptorspec, $pipes, $cwd);
    exec("chown -R " . $nginx['user'] . ": " . $worker['socket_dir']);
    return array(
        "process" => $process,
        "pipes" => $pipes,
        "in_use" => false,
    );
}

//Create new instance of Worker
function createRoutineProcess()
{

    $config = json_decode(file_get_contents("/app/config.json"), true);


    $worker = $config['workers'];
    $nginx = $config['nginx'];

    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("file", "/tmp/worker_error_routine.txt", "a"),
    );

    $cwd = '/tmp';

    $command = "php " . $config['project_dir'] . $worker['file'] . " --env=prd --mode=routine";
    echo $command . PHP_EOL;

    $process = proc_open($command, $descriptorspec, $pipes, $cwd);
    
    return array(
        "process" => $process,
        "pipes" => $pipes,
        "in_use" => false,
    );
}

//Update project from github
function gitUpdate()
{
    
    $config = json_decode(file_get_contents("/app/config.json"), true);

    $git = $config['git'];
    $remote = "https://" . $git['user'] . ":" . $git['token'] . "@github.com/" . $git['git_dir'];
    exec("cd " . $config['project_dir'] . " && git init && git pull $remote " . $git['branch']);
    exec("cd " . $config['project_dir'] . " && composer update");
}

function nginxConfig($list)
{
    $config = json_decode(file_get_contents("/app/config.json"), true);

    $worker = $config['workers'];
    $nginx = $config['nginx'];
    exec("systemctl disable nginx");
    exec("nginx -s quit");
    exec("chown -R " . $nginx['user'] . ": " . $worker['socket_dir']);
    exec("rm -f " . $nginx['load_balance_file'] . " && touch " . $nginx['load_balance_file']);

    $upsteam = "";

    foreach($list as $key => $p){
        $upsteam = $upsteam . "\n server unix:" . $worker['socket_dir'] . "socket_worker" .$key. "_reactphp.sock;";
    }

    $loadbalance_file = 'upstream reactor  {
        least_conn;
        '.$upsteam.'
      }
      server {
        listen       80;
        real_ip_header X-Forwarded-For;
        real_ip_recursive on;
        location / {
            proxy_set_header  Host $host;
            proxy_set_header  X-Real-IP $remote_addr;
            proxy_set_header  X-Forwarded-Proto https;
            proxy_set_header  X-Forwarded $remote_addr;
            proxy_set_header  X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header  X-Forwarded-Host $remote_addr;
            if (!-f $request_filename) {
                proxy_pass http://reactor;
                break;
            }
            try_files $uri $uri/ /index.php?$query_string;
        }
      }';
    
    $var = "";

    file_put_contents($nginx['load_balance_file'], $loadbalance_file);

    exec("nginx");

}

//Create workers
for ($i = 0; $i < $worker['num']; $i++) {
    $process_list[$i] = createProcess($i);
}

if($worker['routines']){
    $routine_process = createRoutineProcess();
}

nginxConfig($process_list);
 
$loop->addPeriodicTimer(1, function () use (&$process_list) {
    foreach ($process_list as $key => $process) {
        $process_info = proc_get_status($process['process']);
        if (!$process_info['running'] && !$process['in_use']) {
            exec('kill ' . $process_info['pid']);
            $terminate = proc_terminate($process['process'], 15);
            if(!$terminate){
                echo "Can't stop the process! " . $process_info['pid'];
            }else{
                $process_list[$key] = createProcess($key);
            }
            
        }
    }

    if(!empty($routine_process)){
        $process_info = proc_get_status($routine_process['process']);
        if (!$process_info['running'] && !$routine_process['in_use']) {
            exec('kill ' . $process_info['pid']);
            $terminate = proc_terminate($routine_process['process'], 15);
            if(!$terminate){
                echo "Can't stop the process! " . $process_info['pid'];
            }else{
                $routine_process = createRoutineProcess();
            }       
        }
    }
});

$loop->addPeriodicTimer($worker['restart'], function () use (&$process_list) {
    foreach ($process_list as $key => $process) {
        $process_info = proc_get_status($process['process']);

        //echo "Restart " . $process_info['pid'] . PHP_EOL;
        $process['in_use'] = true;
        exec('kill ' . $process_info['pid']);
        $terminate = proc_terminate($process['process'], 15);
        if(!$terminate){
            echo "Can't stop the process! " . $process_info['pid'];
        }else{
            $process_list[$key] = createProcess($key);
        }
        $process['in_use'] = false;
    }
    if(!empty($routine_process)){
        $process_info = proc_get_status($routine_process['process']);
        if (!$process_info['running'] && !$routine_process['in_use']) {
            exec('kill ' . $process_info['pid']);
            $terminate = proc_terminate($routine_process['process'], 15);
            if(!$terminate){
                echo "Can't stop the process! " . $process_info['pid'];
            }else{
                $routine_process = createRoutineProcess();
            }       
        }
    }
});

$loop->addPeriodicTimer($git['update'], function () {
    echo "Update Git...". PHP_EOL;
    gitUpdate();
    echo "Project Git Updated!" . PHP_EOL;
});

$loop->run();

for (;;) {}
