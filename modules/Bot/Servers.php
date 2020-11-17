<?php

namespace DBModules\Bot;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Lists\Server;
use DBSnoop\Lists\ServersStatus;
use DBSnoop\System\Cache;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Servers extends ServerRequestControl
{

    /**
     *
     * @Route("/bot/list_servers")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     */
    public function listServers(): Response\JSON
    {
        $filter = array(
            'user' => $this->SESSION['user_id'],
            'removed' => false,
        );

        $servers = new Server($filter);

        return new Response\JSON("ok", $servers->toArray());

    }

    /**
     *
     * @Route("/bot/list_servers_status")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     */
    public function listServersStatus(): Response\JSON
    {
        $filter = array(
            'user' => $this->SESSION['user_id'],
        );

        $servers = new ServersStatus($filter);

        return new Response\JSON("ok", $servers->toArray());

    }
    /**
     *
     * @Route("/bot/list_history")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     */
    public function listHistoric(): Response\JSON
    {

        $filter = array(
            'user' => $this->SESSION['user_id'],
            'removed' => false,
        );

        $servers = new Server($filter);
        $cache = new Cache;

        $format_1 = new \DateInterval('P1D');
        $format_2 = new \DateInterval('PT1M');
        $final_date = new \DateTime(date("Y-m-d H:i"));
        $servers_data = array();
        function calcDiffMinutes(\DateTime $date1, \DateTime $date2): int
        {
            $diff = $date1->diff($date2);
            $minutes = $diff->days * 24 * 60;
            $minutes += $diff->h * 60;
            $minutes += $diff->i;
            return $minutes;

        }
        foreach ($servers->toArray() as $server) {
            $start_date = clone $final_date;

            $start_date->sub($format_1);

            $servers_data[$server['server_id']] = array();

            while (calcDiffMinutes($final_date, $start_date) > 0) {

                $format_date = $start_date->format('Y-m-d H:i');
                $key = "status_server-" . $server['server_id'] . "_" . $format_date;
                $data = $cache->get($key);
                if ($data) {
                    array_push($servers_data[$server['server_id']], $data);
                }
                $start_date->add($format_2);
            }
        }

        return new Response\JSON("ok", $servers_data);

    }

}
