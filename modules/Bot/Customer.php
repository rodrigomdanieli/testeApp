<?php

namespace DBModules\Bot;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entity\Customer as ECustomer;
use DBSnoop\Entity\User;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Customer extends ServerRequestControl
{

    /**
     *
     * @Route("/bot/customer_info")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     */

    public function getCustomerInfo(): Response\JSON
    {
        $user_id = $this->SESSION['user_id'];

        $customer = new ECustomer($this->SESSION['customer_id'] ,new User($user_id));

        return new Response\JSON("ok", $customer->toArray());

    }
}
