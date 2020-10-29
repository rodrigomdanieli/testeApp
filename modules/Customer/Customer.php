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
use DBSnoop\Controller\Email;

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
        
        $email = new Email();
        $user = new User();
        $user->name = $this->REQUEST['user_name'];
        $user->email = $this->REQUEST['email'];
        $user->telephone = $this->REQUEST['phone'];
        $user->preferred_language = $this->REQUEST['language'];

        $customer = new Customers();
        $customer->name = $this->REQUEST['customer_name'];
        $customer->company_code = $this->REQUEST['company_code'];
        $customer->country = $this->REQUEST['country'];

        if(!ExtensionCustomer::createTemporaryCustomer($customer,$user)){
            return new Response\JSON("error", "ERROR_CREAT_USER");
        }

        $email->addAddress($this->REQUEST['email'], $this->REQUEST['user_name']);
    
        $email->Subject = "Cadastro DBSnoop";

        $email->Body = "Essa é a sua senha: " . ExtensionCustomer::createTemporaryCustomer($customer,$user)['validate_code'];

        $email->Send();
        
        //var_dump(ExtensionCustomer::createTemporaryCustomer($customer,$user)['validate_code']);

            return new Response\JSON("ok", ExtensionCustomer::createTemporaryCustomer($customer,$user)['access_hash']);
        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("error", $th->getMessage());
        } catch (\Throwable $th) {
            return new Response\JSON("ok", $th->getMessage());
        }

    }


    /**
     *
     * @Route("/customer/valid_temp_register")
     * @Auth("false")
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "validate_code",
     * "access_hash"
     * })
     */
    public function valid_temp_register(): Response\JSON
    {
        

        try {
            if(!ExtensionCustomer::validateHashTempCustomer($this->REQUEST['access_hash'], $this->REQUEST['validate_code'])){
                return new Response\JSON("error", "INVALID_HASH");
            }

            return new Response\JSON("ok", "ok");
        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("error", $th->getMessage());
        } catch (\Throwable $th) {
            return new Response\JSON("ok", $th->getMessage());
        }

    }
}
