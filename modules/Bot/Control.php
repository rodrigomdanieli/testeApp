<?php

namespace DBModules\Bot;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Annotations\Needed;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Control extends ServerRequestControl
{

    /**
     *
     * @Route("/bot/need_update")
     * @Auth("true")
     * @Type("JSON")
     * @Needed({
     *  "bot"
     * })
     * @Request("POST")
     */

    public function checkNeedUpdate(): Response\JSON
    {
        $user_id = $this->SESSION['user_id'];
        $auth = array ("graphics", "tickets", "servers");
        if(in_array($this->REQUEST['bot'],$auth)){
            return new Response\JSON("ok", "need_update");
        }
        return new Response\JSON("ok", "ok");
    }

    
}
