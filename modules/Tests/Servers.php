<?php

namespace DBModules\Tests;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Extension\Graphic;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;
use DBSnoop\System\Utils;

class Servers extends ServerRequestControl
{

    /**
     *
     * @Route("/test/server/host")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "host"
     * })
     *
     */
    public function test_host(): Response\JSON
    {

        
        try{

            if(empty($this->REQUEST['host'])){
                return new Response\JSON("error", "EMPTY_HOST");
            }

            if (Utils::isIp($this->REQUEST['host'])) {
                if (!Utils::isInternalIP($this->REQUEST['host'])) {
                    return new Response\JSON("ok", "VALID_IP");
               }else{
                return new Response\JSON("error", "INVALID_IP");
               }
           } else if (Utils::isValidHost($this->REQUEST['host'])) {
                return new Response\JSON("ok", "VALID_HOST");
           } else{
            return new Response\JSON("error", "INVALID_HOST");
           }

        }catch(\Throwable $e){
            return new Response\JSON("error", "INTERNAL_ERROR");
        }
    }

    /**
     *
     * @Route("/test/server/port")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "host",
     *  "port"
     * })
     *
     */
    public function test_port(): Response\JSON
    {
        
        try{

            if(empty($this->REQUEST['host'])){
                return new Response\JSON("error", "EMPTY_HOST");
            }
            if(empty($this->REQUEST['port'])){
                return new Response\JSON("error", "EMPTY_PORT");
            }


            $socket = @fsockopen($this->REQUEST['host'], $this->REQUEST['port'], $errno, $errstr, 10);

        
            if($socket){
                return new Response\JSON("ok", "VALID_PORT");
            }else{
                echo $errno . " - " . $errstr . PHP_EOL;
                return new Response\JSON("error", "INVALID_PORT");
            }

        }catch(\Throwable $e){
            return new Response\JSON("error", "");
        }
    }


    

}
