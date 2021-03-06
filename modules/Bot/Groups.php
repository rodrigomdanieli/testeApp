<?php

namespace DBModules\Bot;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Lists\Customer;
use DBSnoop\Lists\Group;
use DBSnoop\Lists\Server;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Groups extends ServerRequestControl
{

    /**
     *
     * @Route("/bot/list_server_groups")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     */

    public function listServerGroups(): Response\JSON
    {
        $user_id = $this->SESSION['user_id'];

        $filter_group = array(
            'user' => $user_id,
        );

        $filter_server = array(
            "only_available" => true,
            "removed" => false,
            "user" => $user_id,

        );

        $servers = new Server($filter_server);

        $groups = new Group($filter_group);

        $customers = new Customer($filter_group);

        $organization = array();

//        return new Response\JSON("ok", $customers->toArray());

        array_map(function ($customer) use (&$organization, $groups, $servers){
            $id = $customer['customer_id'];

            $organization[$id] = array(
                "name" => $customer['customer_name'],
                "id" => $customer['customer_id'],
                "servers" => array(),
                "groups" => array()
            );

            $customer_groups = $groups->Filter(function ($group) use ($id) {
                if($group['customer_id'] == $id)
                    return $group;
            });

            if(!empty($customer_groups)){
                foreach($customer_groups as $group){
                    $group_servers = $servers->Filter(function($server) use ($group){
                        if($server['group_id'] == $group['group_id'])
                            return $server;
                    });
                    $servers_to_add = array();
                    if(!empty($group_servers)){
                        foreach($group_servers as $server){
                            array_push($servers_to_add, array(
                                "id" => $server['server_id'],
                                "name" => $server['server_name']
                            ));
                        }
                    }

                    $organization[$id]['groups'][$group['group_id']] = array(
                        "name" => $group['group_name'],
                        "id" => $group['group_id'],
                        "servers" => $servers_to_add
                    );
                }
            }
            $customer_servers = $servers->Filter(function($server) use ($id){
                if($server['customer_id'] == $id && empty($server['group_id']))
                    return $server;
            });

            if(!empty($customer_servers)){
                foreach($customer_servers as $server){
                    array_push($organization[$id]['servers'], array(
                        "id" => $server['server_id'],
                        "name" => $server['server_name']
                    ));
                }
            }

        },
        $customers->toArray());

        return new Response\JSON("ok", $organization);

    }

    /**
     *
     * @Route("/bot/list_groups")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     */

    public function listGroups(): Response\JSON
    {
        $filter = array(
            'user' => $this->SESSION['user_id'],
        );

        $ticket = new Group($filter);

        return new Response\JSON("ok", $ticket->toArray());

    }
}
