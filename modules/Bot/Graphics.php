<?php

namespace DBModules\Bot;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Extension\Graphic;
use DBSnoop\Lists\Server;
use DBSnoop\System\Cache;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;
use DBSnoop\System\Utils;

class Graphics extends ServerRequestControl
{

    /**
     *
     * @Route("/bot/last_hour_server_graph")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     */

    public function getLastHour(): Response\JSON
    {


        $user_id = $this->SESSION['user_id'];

        $filter_server = array(
            "only_available" => true,
            "removed" => false,
            "user" => $user_id,

        );

        $servers = new Server($filter_server);
        $cache = new Cache;
        $graphs_data = array();

        array_map(function ($server) use (&$graphs_data, $cache) {
            $data = array();
            
            $data['db'] = $cache->get("last_hour_" . $server['db_type'] . "_graph_" . $server['server_id']);
            $data['so'] = $cache->get("last_hour_" . $server['so_type'] . "_graph_" . $server['server_id']);

            $graphs_data[$server['server_id']] = $data;
            
        }, $servers->toArray());

        return new Response\JSON("ok", $graphs_data);

    }

    /**
     *
     * @Route("/bot/get_available_server_graphics")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     */
    public function getAvailableGraphsStatusServer(): Response\JSON
    {
        $graphs_data = array();

        $user_id = $this->SESSION['user_id'];

        $filter_server = array(
            "only_available" => true,
            "removed" => false,
            "user" => $user_id,

        );

        $servers = new Server($filter_server);
        $graph = new Graphic();
        $list_graphics = $graph->getAllStatusGraphic();
        array_map(function ($server) use (&$graphs_data, $list_graphics) {
            $graph_server = array_filter($list_graphics, function ($graph) use ($server) {
                list('graph_service_value' => $type, 'graph_check_column' => $check_column, 'graph_check_value' => $check_value) = $graph;
                if ($server['db_type'] == $type || $server['so_type'] == $type) {
                    if (trim($check_column) != "") {
                        if ($server[$check_column] == $check_value) {
                            return $graph;
                        }

                    } else {
                        return $graph;
                    }

                }
            });

            $graphs_data[$server['server_id']] = array_column($graph_server, "graph_id");
        }, $servers->toArray());

        return new Response\JSON("ok", $graphs_data);

    }

}
