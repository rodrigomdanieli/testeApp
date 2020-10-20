<?php

namespace DBModules\Tickets;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entity\Ticket;
use DBSnoop\Entity\User;
use DBSnoop\Extension\Ticket as ExtensionTicket;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Tickets extends ServerRequestControl
{

    /**
     *
     * @Route("/ticket/check_authorized")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "id"
     * })
     */
    public function checkAuthorizedTicket(): Response\JSON
    {
        $ticket = new Ticket($this->REQUEST['id'], new User($this->SESSION['user_id']));
        return new Response\JSON("ok", array("authorized" => $ticket->checkAuth()));

    }


}
