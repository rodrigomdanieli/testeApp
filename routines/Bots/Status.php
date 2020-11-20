<?php

namespace DBRoutines\Bots;

use DateInterval;
use DBSnoop\Annotations\Active;
use DBSnoop\Annotations\Cron;
use DBSnoop\Annotations\Interval;
use DBSnoop\Annotations\StartRunning;
use DBSnoop\Extension\Server as ExtensionServer;
use DBSnoop\System\CacheRoutines;
use DBSnoop\System\Utils;

class Status
{

    /**
     * @Active
     * @Interval(60)
     * @StartRunning
     */
    public function execStatus()
    {

        $this->processType();
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

            $format_1 = new DateInterval('P1D');
            $format_2 = new DateInterval('PT1M');
            $final_date = new \DateTime(date('Y-m-d 23:59'));
            $final_date->sub($format_1);
            $format_date = $final_date->format('Y-m-d H:i');
            echo 'Start StoreStatus For Day - ' . $format_date . PHP_EOL;
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

                        $s = new \DBSnoop\Entity\Server($server['server_id']);
                        $ext_server = new \DBSnoop\Extension\Server($s);

                        $start_date = clone $final_date;

                        $start_date->sub($format_1);
                        $temp_data = array();

                        while (Utils::calcDiffMinutes($final_date, $start_date) >= 0) {
                            $format_date2 = $start_date->format('Y-m-d H:i');
                            $key_cache2 = sha1("status_history_server-" . $server['server_id'] . "-" . $format_date2);
                            $in_cache = $cache2->get($key_cache2);
                            if (!empty($in_cache)) {
                                $temp_data[$format_date2] = $in_cache;
                            }
                            if (Utils::calcDiffMinutes($final_date, $start_date) == 0) {
                                break;
                            } else {
                                $start_date->add($format_2);
                            }

                        }
                        unset($start_date);

                        if (!empty($temp_data)) {
                            $day = $final_date->format('d');
                            $month = $final_date->format('m');
                            $year = $final_date->format('Y');

                            $path = FILE_PATH . "/store/history_status/$year/$month/$day";
                            $file_name = "server_" . $server['server_id'] . ".json";
                            Utils::writeFile($path, $file_name, json_encode($temp_data));
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

    public function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float) $usec + (float) $sec);
    }

    private function processType()
    {

        try {

            $start = $this->microtime_float();

            $ex = new ExtensionServer();

            $cache = new CacheRoutines;

            $format_1 = new DateInterval('P1D');
            $format_2 = new DateInterval('PT1M');
            $format_3 = new DateInterval('PT5M');
            $final_date = new \DateTime(date("Y-m-d H:i"));

            $format_date = $final_date->format('Y-m-d H:i');
            echo 'Start - ' . $format_date . PHP_EOL;
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
                        function processCache($result)
                        {
                            $result_to_cache = array(
                                "status" => 'G',
                                "alerts" => array(),
                            );
                            foreach ($result as $key => $val) {
                                if ($val['status'] == 'R') {
                                    $result_to_cache['status'] = 'R';
                                } else if ($val['status'] == 'Y' && $result_to_cache['status'] != 'R') {
                                    $result_to_cache['status'] = 'Y';
                                } else if ($val['status'] == 'B' && $result_to_cache['status'] != 'R' && $result_to_cache['status'] != 'Y') {
                                    $result_to_cache['status'] = 'B';
                                }
                                if (!empty($val['alerts'])) {
                                    if (!key_exists($val['service'], $result_to_cache['alerts'])) {
                                        $result_to_cache['alerts'][$val['service']] = array();
                                    }
                                    if (!key_exists($val['type'], $result_to_cache['alerts'][$val['service']])) {
                                        $result_to_cache['alerts'][$val['service']][$val['type']] = array();
                                    }
                                    array_push($result_to_cache['alerts'][$val['service']][$val['type']], array('status' => $val['status'], "alerts" => $val['alerts']));
                                }
                            }
                            return $result_to_cache;
                        }

                        $cache2 = new CacheRoutines;

                        $s = new \DBSnoop\Entity\Server($server['server_id']);
                        $ext_server = new \DBSnoop\Extension\Server($s);

                        $start_date2 = clone $final_date;

                        $start_date2->sub($format_3);

                        while (Utils::calcDiffMinutes($final_date, $start_date2) >= 0) {
                            $format_date2 = $start_date2->format('Y-m-d H:i');
                            
                            //echo "Retroativo - $format_date2" . PHP_EOL;
                            $key_cache2 = sha1("status_history_server-" . $server['server_id'] . "-" . $format_date2);
                            if (!$cache2->exists($key_cache2)) {
                                //echo "Need Retroativo" . PHP_EOL;
                                $result = $ext_server->getHistory($final_date);

                                if ($result['status'] == 'ok') {
                                    $result = $result['data'];

                                    if (!empty($result)) {

                                        $result_to_cache = processCache($result);
                                        $cache2->set($key_cache2, $result_to_cache, 3 * 24 * 60 * 60);
                                    }
                                }

                            }
                            if (Utils::calcDiffMinutes($final_date, $start_date2) == 0) {
                                break;
                            } else {
                                $start_date2->add($format_2);
                            }

                        }
                        
                        $start_date = clone $final_date;

                        $start_date->sub($format_1);
                        $temp_data = array();

                        while (Utils::calcDiffMinutes($final_date, $start_date) >= 0) {
                            $format_date2 = $start_date->format('Y-m-d H:i');
                            $key_cache2 = sha1("status_history_server-" . $server['server_id'] . "-" . $format_date2);
                            $in_cache = $cache2->get($key_cache2);
                            if (!empty($in_cache)) {
                                $temp_data[$format_date2] = $in_cache;
                            }
                            if (Utils::calcDiffMinutes($final_date, $start_date) == 0) {
                                break;
                            } else {
                                $start_date->add($format_2);
                            }

                        }
                        unset($start_date);

                        if (!empty($temp_data)) {
                            $cache2->set("last_day_status" . $server['server_id'], $temp_data, 1800);
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

}
