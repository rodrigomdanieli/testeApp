<?php

namespace DBModules\Customer;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entity\Customer as Customers;
use DBSnoop\Entity\User;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;
use DBSnoop\Extension\Customer as ExtensionCustomer;

class Customer extends ServerRequestControl
{
    /**
     *
     * @Route("/customer/temp_register")
     * @Auth("false")
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "user_name",
     * "customer_name",
     * "email",
     * "company_code",
     * "country",
     * "phone",
     * "language"
     * })
     */
    public function temp_register(): Response\JSON
    {
        

        try {

        $user = new User();
        $user->user_name = $this->REQUEST['user_name'];
        $user->email = $this->REQUEST['email'];
        $user->phone = $this->REQUEST['phone'];
        $user->language = $this->REQUEST['language'];

        $customer = new Customers();
        $customer->customer_name = $this->REQUEST['customer_name'];
        $customer->company_code = $this->REQUEST['company_code'];
        $customer->country = $this->REQUEST['country'];

        //ExtensionCustomer::createTemporaryCustomer($customer,$user)
        //var_dump(ExtensionCustomer::createTemporaryCustomer($customer,$user));

        // if(!ExtensionCustomer::createTemporaryCustomer($customer,$user)){
        //     return new Response\JSON("error", "ERROR_CREAT_USER");
        // }

            return new Response\JSON("ok", ExtensionCustomer::createTemporaryCustomer($customer,$user));
        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("error", $th->getMessage());
        } catch (\Throwable $th) {
            return new Response\JSON("ok", $th->getMessage());
        }

    }
}
