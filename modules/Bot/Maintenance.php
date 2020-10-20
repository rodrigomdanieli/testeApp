<?php

namespace DBModules\Bot;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Lists\Maintenance as LMaintenance;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Maintenance extends ServerRequestControl
{

    /**
     *
     * @Route("/bot/list_maintenance")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     */
    public function listMaintenances(): Response\JSON
    {
        $user_id = $this->SESSION['user_id'];

        $filter = array(
            'user' => $user_id,
        );

        $maintenance = new LMaintenance($filter);

        return new Response\JSON("ok", $maintenance->toArray());

    }
}
