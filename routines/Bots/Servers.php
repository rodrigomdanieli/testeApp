<?php

namespace DBRoutines\Bots;

use DBSnoop\Annotations\Active;
use DBSnoop\Annotations\Cron;
use DBSnoop\Annotations\Interval;
use DBSnoop\Annotations\StartRunning;
use DBSnoop\Entity\Server as EntityServer;
use DBSnoop\Extension\Graphic as ExtensionGraphic;
use DBSnoop\Extension\Server as ExtensionServer;
use DBSnoop\System\Cache;
use DBSnoop\System\CacheRoutines;
use DBSnoop\System\Utils;

class Servers
{

    /**
     * @Active
     * @Interval(60)
     * @StartRunning
     */
    public function upMysqlGraphic()
    {

        $this->processType("mysql");
    }

    /**
     * @Active
     * @Interval(60)
     * @StartRunning
     */
    public function upLinuxGraphic()
    {

        $this->processType("linux");
    }

    /**
     * @Active
     * @Interval(60)
     * @StartRunning
     */
    public function upOracleGraphic()
    {

        $this->processType("oracle");
    }

    /**
     * @Active
     * @Interval(60)
     * @StartRunning
     */
    public function upDB2Graphic()
    {

        $this->processType("db2");
    }

    /**
     * @Active
     * @Interval(60)
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

    /**
     * @Active
     * @Cron("@daily")
     *
     */
    public function storeStatus()
    {

        try {

            $start = $this->microtime_float();

            $ex = new ExtensionServer();

            $format_1 = new \DateInterval('P1D');
            $format_2 = new \DateInterval('PT1M');
            $final_date = new \DateTime(date('Y-m-d 23:59'));
            $final_date->sub($format_1);
            $format_date = $final_date->format('Y-m-d H:i');
            echo 'Start StoreGraph For Day - ' . $format_date . PHP_EOL;
            $for2 = $ex->getAllActiveServers();
            $chunks = array_chunk($for2, 150);

            foreach ($chunks as $chunk) {
                foreach ($chunk as $server) {
                    $pid = pcntl_fork();

                    if ($pid == -1) {
                        //die('pcntl_fork failed');
                    } elseif ($pid) {
                        $childPids[] = $pid;
                    } else {
                        $cache2 = new CacheRoutines;
                        $start_date = clone $final_date;

                        $start_date->sub($format_1);
                        $so = $server['so_type'];
                        $db = $server['db_type'];

                        $temp_data_db = array();
                        $temp_data_so = array();

                        while (Utils::calcDiffMinutes($final_date, $start_date) >= 0) {
                            $format_date2 = $start_date->format('Y-m-d H:i');

                            $key_cache_so2 = sha1($so . "_graph_" . $server['server_id'] . "_" . $format_date2);
                            $key_cache_db2 = sha1($db . "_graph_" . $server['server_id'] . "_" . $format_date2);
                            $c_so = $cache2->get($key_cache_so2);
                            $c_db = $cache2->get($key_cache_db2);
                            if (is_array($c_so)) {
                                if (empty($temp_data_so)) {
                                    $temp_data_so = $c_so;
                                } else {
                                    foreach ($temp_data_so as $key => $graph) {
                                        if (is_array($graph) && isset($c_so[$key]) && is_array($c_so[$key])) {
                                            $temp_data_so[$key] = array_merge($c_so[$key], $graph);
                                        }
                                    }
                                }
                            }
                            if (is_array($c_db)) {
                                if (empty($temp_data_db)) {
                                    $temp_data_db = $c_db;
                                } else {
                                    foreach ($temp_data_db as $key => $graph) {
                                        if (is_array($graph) && isset($c_db[$key]) && is_array($c_db[$key])) {
                                            $temp_data_db[$key] = array_merge($c_db[$key], $graph);
                                        }
                                    }
                                }
                            }
                            if (Utils::calcDiffMinutes($final_date, $start_date) == 0) {
                                break;
                            } else {
                                $start_date->add($format_2);
                            }

                        }
                        unset($start_date);

                        if (!empty($temp_data_so)) {
                            $day = $final_date->format('d');
                            $month = $final_date->format('m');
                            $year = $final_date->format('Y');

                            $path = FILE_PATH . "/store/graphics/$so/$year/$month/$day";
                            $file_name = "server_" . $server['server_id'] . ".json";
                            Utils::writeFile($path, $file_name, json_encode($temp_data_so));
                        }

                        if (!empty($temp_data_db)) {
                            $day = $final_date->format('d');
                            $month = $final_date->format('m');
                            $year = $final_date->format('Y');

                            $path = FILE_PATH . "/store/graphics/$db/$year/$month/$day";
                            $file_name = "server_" . $server['server_id'] . ".json";
                            Utils::writeFile($path, $file_name, json_encode($temp_data_db));
                        }

                        unset($cache2);
                        unset($ext_server);
                        unset($s);

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
            $end = $this->microtime_float();

            echo " Time to exec: " . ($end - $start) . " seconds" . PHP_EOL;
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

    private function processType($type)
    {
        $start_timer = $this->microtime_float();

        $servers = new ExtensionServer();
        $cache = new CacheRoutines;
        $format_1 = new \DateInterval('PT1H');
        $format_2 = new \DateInterval('PT1M');
        $format_3 = new \DateInterval('PT5M');
        $final_date = new \DateTime(date("Y-m-d H:i"));

        echo 'Start - ' . $type . ' - ' . $final_date->format('Y-m-d H:i') . PHP_EOL;
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
                        $end_date = clone $final_date;
                        $end_date->sub($format_3);

                        $start_date = clone $end_date;

                        $start_date->sub($format_2);

                        while (Utils::calcDiffMinutes($final_date, $end_date) >= 0) {
                            $start = $start_date->format('Y-m-d H:i');
                            $end = $end_date->format('Y-m-d H:i');

                            $hash = sha1($type . "_graph_" . $server['server_id'] . "_" . $end);
                            //echo "Check Retroativo - ". $end_date->format('Y-m-d H:i') . PHP_EOL;
                            if(!$cache2->exists($hash)){
                                //echo "Is Retroativo" . PHP_EOL;
                                $graph = new ExtensionGraphic(new EntityServer($server['server_id']));
                                $s = $graph->getAllDataGraphicsByType($start, $end, $type);

                                $cache2->set($hash, $s, 3 * 24 * 60 * 60);
                                
                                unset($graph);
                            }
                            if (Utils::calcDiffMinutes($final_date, $end_date) == 0) {
                                break;
                            } else {
                                $start_date->add($format_2);
                                $end_date->add($format_2);
                            }
                        }
                        unset($cache2);
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

        foreach ($for2 as $server) {
            if ($server['db_type'] == $type || $server['so_type'] == $type) {

                $start_date = clone $final_date;

                $start_date->sub($format_1);
                $temp_data = array();

                while (Utils::calcDiffMinutes($final_date, $start_date) >= 0) {
                    $format_date2 = $start_date->format('Y-m-d H:i');
                    $key_cache2 = sha1($type . "_graph_" . $server['server_id'] . "_" . $format_date2);
                    $c = $cache->get($key_cache2);
                    if (is_array($c)) {
                        if (empty($temp_data)) {
                            $temp_data = $c;
                        } else {
                            foreach ($temp_data as $key => $graph) {
                                if (is_array($graph) && isset($c[$key]) && is_array($c[$key])) {
                                    $temp_data[$key] = array_merge($c[$key], $graph);
                                }
                            }
                        }
                    }
                    if (Utils::calcDiffMinutes($final_date, $start_date) == 0) {
                        break;
                    } else {
                        $start_date->add($format_2);
                    }

                }
                if (!empty($temp_data)) {
                    $cache->set("last_hour_" . $type . "_graph_" . $server['server_id'], $temp_data, 1800);
                }

                unset($start_date);
            }

        }

        $end_timer = $this->microtime_float();

        echo 'End - ' . $type . ' - ' . ($end_timer - $start_timer) . " seconds" . PHP_EOL;

    }

}
