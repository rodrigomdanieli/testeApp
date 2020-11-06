<?php

namespace DBModules\Dashboard;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entity\User;
use DBSnoop\Extension\Graphic;
use DBSnoop\Extension\Server;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Overview extends ServerRequestControl
{

    /**
     *
     * @Route("/overview/get_alert_graphic")
     * @Auth(true)
     *
     */
    public function alertGraphic(): Response\JSON
    {
        try{
            $data = Server::getAlertsGraphic(new User($this->SESSION['user_id']));
            return new Response\JSON($data['status'], $data['data']);
        }catch(\Throwable $e){
            return new Response\JSON("error", $e->getMessage());
        }catch(\Exception $e){
            return new Response\JSON("error", $e->getMessage());
        }
    }

}
