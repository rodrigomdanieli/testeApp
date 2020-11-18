<?php

namespace DBModules\Bot;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Annotations\Needed;
use DBSnoop\Entity\Server as EntityServer;
use DBSnoop\Entity\User;
use DBSnoop\Lists\Server;
use DBSnoop\Lists\ServersStatus;
use DBSnoop\System\Cache;
use DBSnoop\System\CacheRoutines;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Servers extends ServerRequestControl
{

    /**
     *
     * @Route("/bot/list_servers")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     */
    public function listServers(): Response\JSON
    {
        $filter = array(
            'user' => $this->SESSION['user_id'],
            'removed' => false,
        );

        $servers = new Server($filter);

        return new Response\JSON("ok", $servers->toArray());

    }

    /**
     *
     * @Route("/bot/list_servers_status")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     */
    public function listServersStatus(): Response\JSON
    {
        $filter = array(
            'user' => $this->SESSION['user_id'],
        );

        $servers = new ServersStatus($filter);

        return new Response\JSON("ok", $servers->toArray());

    }
    
    /**
     *
     * @Route("/bot/list_history")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     */
    public function listHistoric(): Response\JSON
    {
        $filter = array(
            'user' => $this->SESSION['user_id'],
            'removed' => false,
        );

        $servers = new Server($filter);

        $cache = new Cache;

        $servers_data = array();
        foreach($servers->toArray() as $server){
            $servers_data[$server['server_id']] = $cache->get('last_day_status'.$server['server_id']);
        }
        
        return new Response\JSON("ok", $servers_data);

    }

}
