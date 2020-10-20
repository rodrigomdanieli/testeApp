<?php

namespace DBModules\Auth;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entities\Login as DBLogin;
use DBSnoop\System\Authentication;
use DBSnoop\System\ServerRequestControl;
use DBSnoop\System\Response;

class Login extends ServerRequestControl
{
    /**
     * @Route("/auth/login")
     * @Auth(false)
     * @Request("POST")
     * @Needed({
     *      "username",
     *      "password"
     * })
     * @Type("JSON")
     */
    public function login() : Response\JSON
    {
        $user = $this->REQUEST['username'];
        $pass = $this->REQUEST['password'];
        $login = new DBLogin();

        $response = array(
            "status"    => "ok",
            "data"      => array()
        );
        echo $user . $pass . PHP_EOL;
        $verified = $login->doLogin($user, $pass);
        var_dump($verified);
        if ($verified['status'] == "ok") {
            if (empty($verified['data'])) {
                $response["status"] = "error";
                $response["data"] = "INVALID LOGIN!";

            }else{
                $auth = new Authentication();
                $token = $auth->createNewAuthentication($verified['data'][0]);
                $response["data"] = array('token' => $token);

            }

        }

        return new Response\JSON($response["status"], $response["data"]);

    }

    /**
     * @Route("/auth/check")
     * @Auth(true)
     * @Request("GET")
     */

    public function check_login($request) : Response\JSON
    {

        return new Response\JSON("ok", $this->SESSION);

    }

    /**
     * @Route("/auth/logout")
     * @Auth(true)
     * @Request("GET")
     */
    public function logout($request) : Response\JSON
    {

        $auth = new Authentication($this->SESSION_ID);
        $auth->destroySession();
        return new Response\JSON("ok", array("logout OK"));

    }

}
