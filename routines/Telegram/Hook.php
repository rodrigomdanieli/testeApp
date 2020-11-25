<?php

namespace DBRoutines\Telegram;

use DBSnoop\Annotations\Active;
use DBSnoop\Annotations\Cron;
use DBSnoop\Annotations\Interval;
use DBSnoop\Annotations\StartRunning;
use DBSnoop\Controller\Telegram;

use DBSnoop\Extension\User as EUser;
use DBSnoop\Entity\User;
use DBSnoop\Lists\ServersStatus;
use DBSnoop\System\Cache;

class Hook{
    
    private $available_methods;

    function __construct()
    {
        $this->available_methods = get_class_methods($this);
    }

    /**
     * @ Active
     * @StartRunning
     */
    public function configureTelegram()
    {

        $commands = array(
            array(
                "command" => "help",
                "description" => "Get help about how to use the bot"
            ),
            array(
                "command" => "cache",
                "description" => "Get help about how to use the bot"
            ),
            array(
                "command" => "registerinfo",
                "description" => "Check your account registration info"
            ),
            array(
                "command" => "get_servers_error",
                "description" => "Get all alerts and errors found"
            ),
            array(
                "command" => "get_servers_alert",
                "description" => "Get all alerts found"
            ),
            array(
                "command" => "get_status_server",
                "description" => "Get a status report from all servers"
            ),
        );

        Telegram::setCommands($commands);
        
    }

    /**
     * @Active
     * @Interval(1)
     */
    public function hook()
    {   
        $cache = new Cache;
        $last_id = $cache->get('last_update_telegram_id');
        if(!$last_id)
            $last_id = 0;
        $updates = json_decode(Telegram::sendToTelegram('/getUpdates', array(
            'offset' => $last_id + 1
        )),true);
        

        if(isset($updates['ok']) && $updates['ok']){
            $last = end($updates['result']);
            if($last){
                $cache->set('last_update_telegram_id', $last['update_id'],0);
            }
            foreach($updates['result'] as $messages){
                $messages = $messages['message'];
                $chat = $messages['from']['id'];
                $check_user = EUser::getUserByTelegram($chat);
                if($check_user){
                    $user = new User($check_user['user_id']);

                    $method = "process". strtoupper(substr($messages['text'], 1, 1)) . substr($messages['text'], 2);

                    if(in_array($method, $this->available_methods))
                    {
                        $response = self::$method($user, $messages);
                        if(is_array($response)){
                            foreach($response as $msg){
                                echo "MESSAGE" . PHP_EOL;
                                echo $msg . PHP_EOL;
                                Telegram::sendMessage($chat , $msg);
                            }
                        }else{
                            Telegram::sendMessage($chat , $response);
                        }
                    }else{
                        Telegram::sendMessage($chat , "INVALID METHOD!");
                    }
                }else{
                    Telegram::sendMessage($chat , "INVALID USER!");
                }
            }
        }
    }

    public function processHelp()
    {
        return "Processado!";


    }

    public function processTeste_rodolfo($user)
    {

        return "Fiz a porra toda!" . $user->getName();


    }

    public function processCache($user){

        $filter = array(
            'user' => $user->getId()
        );
        $server_list = new ServersStatus($filter);

        $list = "";

        foreach($server_list->toArray() as $server){
            if($server['status'] == 'R'){
                $list .= " - [". $server['customer_name'] ."]" . $server['server_name'] . " - ". $server['alerts'] . "\n";
            }
        }
        $list2= [];
        $list2[] = substr($list, 0, 4000);
        $list2[] = substr($list, 4000, 7000);
        return $list2;

    }
}