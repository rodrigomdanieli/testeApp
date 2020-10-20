<?php

namespace DBModules\Bot;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Lists\Ticket;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Tickets extends ServerRequestControl
{

    /**
     *
     * @Route("/bot/list_opened_ticket")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     */

    public function listOpenedTicket(): Response\JSON
    {
        $filter = array(
            'status' => 'All Open',
            'user' => $this->SESSION['user_id']
        );

        $ticket = new Ticket($filter);

        return new Response\JSON("ok", $ticket->toArray());

    }
    
    /**
     *
     * @Route("/bot/list_closed_ticket")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     */
    public function listClosedTicket(): Response\JSON
    {
        $filter = array(
            'status' => 'All Closed',
            'start' => date('Y-m-d', strtotime('-7 days')),
            'user' => $this->SESSION['user_id']
        );

        $ticket = new Ticket($filter);

        return new Response\JSON("ok", $ticket->toArray());

    }
}
