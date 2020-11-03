<?php

namespace DBModules\User;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entity\User;
use DBSnoop\Extension\User as ExtensionUser;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Users extends ServerRequestControl
{
    /**
     *
     * @Route("/user/get_info")
     * @Auth(false)
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
    
    /** 
     * @Route("/user/create_first_password")
     * @Auth(false)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "access_hash",
     * "new_password"
     * })
     */
    public function create_first_password(): Response\JSON
    {
        $hash = $this->REQUEST['access_hash'];
        $new_password = $this->REQUEST['new_password'];
        $ext = new ExtensionUser();
        try {
            if ($ext->changeFirstPassword($hash, $new_password)) {
                return new Response\JSON("ok", "INVALID_USER");
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
