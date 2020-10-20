<?php

namespace DBModules\Servers;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Route;
use DBSnoop\Entities\Server;
use DBSnoop\System\ServerRequestControl;
use DBSnoop\System\Response;


class Alerts extends ServerRequestControl
{

    /**
     *
     * @Route("/servers/getCount")
     * @Auth("true")
     */
    public function getCounts($request) : Response\JSON
    {

        $server = new Server();
        $count = $server->getCounts($this->SESSION['user_id']);

        if ($count['status'] == "ok") {
            array(
                "counts" => $count['data'],
            );
        }

        return new Response\JSON("ok", array(
            "counts" => $count['data'],
        ));
    }
    /**
     *
     * @Route("/servers/getGraph")
     * @Auth("true")
     *
     *
     */
    public function getGraph($request) : Response\JSON
    {

        $server = new Server();
        $count = $server->getGraph($this->session['user_id']);

        
        return new Response\JSON("ok", array(
            "counts" => $count['data'],
        ));
    }

}
