<?php

namespace DBModules\Tickets;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Lists\Ticket;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class ListTicket extends ServerRequestControl
{

    /**
     *
     * @Route("/ticket/list")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "ticket",
     *  "customer",
     *  "group",
     *  "server",
     *  "status",
     *  "type",
     *  "you",
     *  "start",
     *  "end",
     *  "commentary"
     * })
     */
    public function getList(): Response\JSON
    {
        var_dump($this->REQUEST);
        $params = $this->REQUEST;
        $params['user'] = $this->SESSION['user_id'];
        $ticket = new Ticket($params);

        return new Response\JSON("ok", $ticket->toArray());

    }
}
