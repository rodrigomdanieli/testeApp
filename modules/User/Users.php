<?php

namespace DBModules\User;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entity\User;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Users extends ServerRequestControl
{
    /**
     *
     * @Route("/user/get_info")
     * @Auth("false")
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "id"
     * })
     */
    public function get_user_info(): Response\JSON
    {
        

        try {

            $user = new User($this->REQUEST['id'], new User($this->SESSION['user_id']));
            return new Response\JSON("ok", $user->toArray());
        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("error", $th->getMessage());
        } catch (\Throwable $th) {
            return new Response\JSON("ok", $th->getMessage());
        }

    }
}
