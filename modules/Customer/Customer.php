<?php

namespace DBModules\Customer;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\System\ServerRequestControl;
use DBSnoop\System\Response;
use DBSnoop\Extension\Customer as ExtensionCustomer;
use DBSnoop\Entity\Customer as EntityCustomer;
use DBSnoop\Entity\User;




class Customer extends ServerRequestControl
{
    /**
     *
     * @Route("/customer/new_customer_group")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "customer_name"
     * })
     */
    public function new_customer_group(): Response\JSON
    {
        $customer_name = $this->REQUEST['customer_name'];

        if(empty($customer_name)){
            return new Response\JSON("error", "EMPTY_CUSTOMER_NAME");
        }

        $customer = new EntityCustomer($this->SESSION['customer_id'], new User($this->SESSION['user_id']));
        $customer_extension = new ExtensionCustomer($customer);
        $customer_extension->createNewCustomerGroup($customer_name);

        // if($customer_extension['status'] != 'ok'){
        //     return new Response\JSON("error", $customer_extension['msg']);
        // }else{
        //     return new Response\JSON("OK", "OK");
        // }
        return new Response\JSON("ok", "OK");
        
    }
}
