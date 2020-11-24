<?php

namespace DBModules\Telegram;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\System\Cache;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;
use DBSnoop\System\Utils;

class Control extends ServerRequestControl
{
    /**
     * @Route("/telegram/hook")
     * @Auth(false)
     * @Request("GET")
     * @Needed({
     *  "update_id",
     *  "message"
     * })
     */
    public function hook() : Response\JSON
    {

        $response = '';

        var_dump($this->REQUEST['message']);




        return new Response\JSON('ok', $response);
    }

    private static function processHelp(\DBSnoop\Entity\User $user, $message) : Response\JSON
    {

        
        return new Response\JSON('ok', 'ok');
    }

}
