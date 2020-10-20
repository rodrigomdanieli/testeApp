<?php

namespace DBRoutines\Bots;

use DBSnoop\Annotations\Active;
use DBSnoop\Annotations\Interval;
use DBSnoop\Annotations\StartRunning;
use DBSnoop\Entity\Server as EntityServer;
use DBSnoop\Extension\Graphic as ExtensionGraphic;
use DBSnoop\Extension\Server as ExtensionServer;
use DBSnoop\System\CacheRoutines;
use DBSnoop\System\Utils;

class Servers
{

    /**
     *
     * @Active
     * @Interval(180)
     * @StartRunning
     */
    public function upMysqlGraphic()
    {

        $this->processType("mysql");
    }

    /**
     * @Active
     * @Interval(180)
     * @StartRunning
     */
    public function upLinuxGraphic()
    {

        $this->processType("linux");
    }

    /**
     * @Active
     * @Interval(180)
     * @StartRunning
     */
    public function upOracleGraphic()
    {

        $this->processType("oracle");
    }

    /**
     * @Active
     * @Interval(180)
     * @StartRunning
     */
    public function upDB2Graphic()
    {

        $this->processType("db2");
    }

    /**
     * @Active
     * @Interval(180)
     * @StartRunning
     */
    public function upMSSQLGraphic()
    {
        $this->processType("mssql");
    }

    public function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float) $usec + (float) $sec);
    }

    private function processType($type)
    {

        $start = $this->microtime_float();

        $servers = new ExtensionServer();

        $cache = new CacheRoutines;

        $minute = date("i");
        $period = 2;
        
        $sum = $minute % $period;
        $minute = $minute - $sum ;
        $date = new \DateTime(date("Y-m-d H") . ":$minute");
        echo "Start $type (".$date->format("Y-m-d H:i").")" . PHP_EOL;
        $limit = (60 / $period) * 1;
        $limit = 1;

        $for2 = $servers->getAllActiveServers();
        $chunks = array_chunk($for2, 150);
        foreach ($chunks as $chunk) {
            foreach ($chunk as $server) {
                $pid = pcntl_fork();

                if ($pid == -1) {
                    //die('pcntl_fork failed');
                } elseif ($pid) {
                    $childPids[] = $pid;
                } else {
                    if ($server['db_type'] == $type || $server['so_type'] == $type) {

                        $cache2 = new CacheRoutines;

                        $graph = new ExtensionGraphic(new EntityServer($server['server_id']));

                        $count = 0;
                        $current_date = new \DateTime($date->format("Y-m-d H:i:s"));
                        while ($count < $limit) {
                            $count++;
                            $end = $current_date->format("Y-m-d H:i:s");
                            $start = $current_date->modify("-".$period." minutes");
                            $s = $graph->getAllDataGraphicsByType($start->format("Y-m-d H:i:s") , $end, $type);
                            $hash = sha1($type . "_graph_" . $server['server_id'] . "_" . $current_date->format("Y-m-d H:i:s"));

                            $cache2->set($hash, $s, 3600);
                            $current_date = $start;
                        }
                        unset($cache2);
                        unset($graph);
                    }

                    exit();
                }
            }

            while (!empty($childPids)) { //wait for all children to complete
                foreach ($childPids as $key => $pid) {
                    $status = null;
                    $res = pcntl_waitpid($pid, $status, WNOHANG);

                    if ($res == -1 || $res > 0) { //if the process has already exited
                        unset($childPids[$key]);
                    }
                }

            }

        }

        
        $limit = (60 / $period) * 1;

        foreach ($for2 as $server) {
            if ($server['db_type'] == $type || $server['so_type'] == $type) {
                $count = 0;
                $current_date = new \DateTime($date->format("Y-m-d H:i:s"));
                $temp_data = array();
                while ($count <= $limit) {
                    $count++;
                    $hash = sha1($type . "_graph_" . $server['server_id'] . "_" . $current_date->format("Y-m-d H:i:s"));
                    $c = $cache->get($hash);
                    if (is_array($c)) {
                        if (empty($temp_data)) {
                            $temp_data = $c;
                        } else {
                            foreach ($temp_data as $key => $graph) {
                                if (is_array($graph) && isset($c[$key]) && is_array($c[$key])) {
                                    $temp_data[$key] = array_merge($c[$key],$graph);
                                }
                            }
                        }
                    }
                    $current_date = $current_date->modify("-".$period." minutes");
                    //$cache->destroy($hash);
                }
                if(!empty($temp_data)){
                    $cache->set("last_hour_" . $type . "_graph_" . $server['server_id'], $temp_data, 1800);
                }
                unset($temp_data);
                
            }
        }
        
        unset($for2);

        $end = $this->microtime_float();

        echo $type . " Time to exec: " . ($end - $start) . " seconds" . PHP_EOL;

    }

}
