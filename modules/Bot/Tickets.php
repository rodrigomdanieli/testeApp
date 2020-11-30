<?php

namespace DBModules\Bot;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entity\User;
use DBSnoop\Extension\Ticket as ExtensionTicket;
use DBSnoop\Lists\Ticket;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Tickets extends ServerRequestControl
{

    /**
     *
     * @Route("/bot/list_opened_ticket")
     * @Auth(true)
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
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     */
    public function listClosedTicket(): Response\JSON
    {
        $filter = array(
            'status' => 'All Closed',
            'start' => date('Y-m-d', strtotime('-'.$this->SESSION['data_retention'].' days')),
            'user' => $this->SESSION['user_id']
        );

        $ticket = new Ticket($filter);

        return new Response\JSON("ok", $ticket->toArray());

    }

    /**
     *
     * @Route("/bot/get_sla_tickets")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     */
    public function getSLA() : Response\JSON
    {   
        $user = new User($this->SESSION['user_id']);
        $sla = ExtensionTicket::getSLA($user);

        return new Response\JSON($sla['status'], $sla['data']);

    }
}
