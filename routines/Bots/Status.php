<?php

namespace DBRoutines\Bots;

use DateInterval;
use DBSnoop\Annotations\Active;
use DBSnoop\Annotations\Interval;
use DBSnoop\Annotations\StartRunning;
use DBSnoop\Extension\Server as ExtensionServer;
use DBSnoop\System\CacheRoutines;

class Status
{

    /**
     *
     * @Active
     * @Interval(60)
     * @StartRunning
     */
    public function execStatus()
    {

        $this->processType();
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
            $final_date = new \DateTime(date("Y-m-d H:i"));
            echo 'Start - ' . $final_date->format('Y-m-d H:i') . PHP_EOL;
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

                        $start_date = clone $final_date;

                        $start_date->sub($format_1);

                        $cache2 = new CacheRoutines;

                        $s = new \DBSnoop\Entity\Server($server['server_id']);
                        $ext_server = new \DBSnoop\Extension\Server($s);
                        
                        while ($this->calcDiffMinutes($final_date, $start_date) > 0) {
                            $format_date = $start_date->format('Y-m-d H:i');
                            $key = "status_server-" . $server['server_id'] . "_" . $format_date;
                            if (!$cache2->exists($key)) {
                                $result = $ext_server->getHistory($start_date);
                                $result_to_cache = array(
                                    "timestamp" => $format_date,
                                    "server" => $server['server_id'],
                                    "status" => 'G',
                                    "alerts" => array(),
                                );
                                if ($result['status'] == 'ok') {
                                    $result = $result['data'];

                                    if (!empty($result)) {
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
                                                array_push($result_to_cache['alerts'][$val['service']][$val['type']], $val['alerts']);
                                            }
                                        }
                                    }
                                    $cache2->set($key, $result_to_cache, $final_date->diff($start_date)->format('s'));
                                }

                            }
                            $start_date->add($format_2);

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

            $end = $this->microtime_float();

            echo " Time to exec: " . ($end - $start) . " seconds" . PHP_EOL;
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

    private function calcDiffMinutes(\DateTime $date1, \DateTime $date2)
    {
        $diff = $date1->diff($date2);
        $minutes = $diff->days * 24 * 60;
        $minutes += $diff->h * 60;
        $minutes += $diff->i;
        return $minutes;

    }

}
