<?php

namespace DBModules\Auth;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entity\Customer;
use DBSnoop\Entity\User;
use DBSnoop\Extension\User as ExtensionUser;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Validation extends ServerRequestControl
{
    /**
     *
     * @Route("/auth/valid_email_domain")
     * @Auth("false")
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "email"
     * })
     */
    public function valid_email_domain(): Response\JSON
    {
        $email = $this->REQUEST['email'];
        if(empty($email)){
            return new Response\JSON("error", "EMPTY_EMAIL_FIELD");
        }

        if(!ExtensionUser::validUserEmail($email)){
            return new Response\JSON("error", "INVALID_EMAIL");
        }

        try {
            return new Response\JSON("ok", "VALID_EMAIL");
        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("error", $th->getMessage());
        } catch (\Throwable $th) {
            return new Response\JSON("ok", $th->getMessage());
        }

    }
}
