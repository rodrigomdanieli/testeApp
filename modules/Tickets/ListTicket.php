<?php

namespace DBModules\Tickets;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entities\Lists\Ticket;
use DBSnoop\System\ServerRequestControl;
use DBSnoop\System\Response;

class ListTicket extends ServerRequestControl
{

    /**
     *
     * @Route("/ticket/list")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "ticket_id",
     *  "customer_id",
     *  "group_id",
     *  "server_id",
     *  "status",
     *  "type",
     *  "you",
     *  "start",
     *  "end",
     *  "commentary"
     * })
     */
    public function getCounts() : Response\JSON
    {

        $params = $this->REQUEST;
        $params['user_id'] = $this->SESSION['user_id'];

        $ticket = new Ticket($params);
        $list = $ticket->getList();
        
        if ($list['status'] == "ok") {
            return new Response\JSON("ok", $list['data']);
        }else{
            return new Response\JSON("error", $list);
        }
    }
}