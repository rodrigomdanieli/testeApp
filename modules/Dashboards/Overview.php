<?php

namespace DBModules\Dashboards;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entities\Server;
use DBSnoop\System\ServerRequestControl;
use DBSnoop\System\Response;


class Overview extends ServerRequestControl
{

    /**
     *
     * @Route("/overview/get/countsBar")
     * @Auth("true")
     * @Request("POST")
     * @Type("JSON")
     */
    public function getCounts() : Response\JSON
    {

        $server = new Server();
        $count = $server->getCounts($this->SESSION['user_id']);

        if ($count['status'] == "ok") {
            return new Response\JSON("ok", $count['data'][0]);
        }else{
            return new Response\JSON("error", $count);
        }
    }
    /**
     *
     * @Route("/overview/get/graphAlerts")
     * @Auth("true")
     * @Request("POST")
     * @Type("JSON")
     *
     */
    public function getGraph() : Response\JSON
    {

        $server = new Server();
        $graph = $server->getGraph($this->SESSION['user_id']);

        if ($graph['status'] == "ok") {
            return new Response\JSON("ok", $graph['data']);
        }else{
            return new Response\JSON("error", $graph);
        }
    }

}
