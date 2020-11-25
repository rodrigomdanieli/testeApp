<?php

namespace DBModules\Bot;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
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
        $organization = array();

        array_map(function ($server) use ($groups, &$organization) {
            $id = $server['customer_id'];
            if (!key_exists($id, $organization)) {
                $customer = @array_shift(
                    $groups->Filter(function ($group) use ($id) {
                        if ($group['customer_id'] == $id) {
                            return $group;
                        }
                    })
                );
                if($id == "1"){
                    var_dump($customer);
                }
                $organization[$server['customer_id']] = array(
                    "name" => $customer['customer_name'],
                    "id" => $customer['customer_id'],
                    "servers" => array(),
                    "groups" => array(),
                );
            }
            
            $tmp_groups = $groups->Filter(function ($group) use ($id) {
                if ($group['customer_id'] == $id) {
                    return $group;
                }
            });

            if(!empty($tmp_groups)){
                foreach($tmp_groups as $group){
                    $organization[$id]['groups'][$group['group_id']] = array(
                        "name" => $group['group_name'],
                        "id" => $group['group_id'],
                        "servers" => array()
                    );
                }
            }


            if (is_numeric($server['group_id'])) {
                $group_id = $server['group_id'];
                
                if(!key_exists($group_id, $organization[$id]['groups'])){
                    $group = @array_shift($groups->Filter(function ($group) use ($group_id) {
                        if ($group['group_id'] == $group_id) {
                            return $group;
                        }
                    }));

                    $organization[$id]['groups'][$group_id] = array(
                        'name'      => $group['group_name'],
                        'id'        => $group['group_id'],
                        'servers'   => array() 
                    );
                }

                array_push($organization[$id]['groups'][$group_id]['servers'], array(
                    'id'    => $server['server_id'],
                    'name'  => $server['server_name']
                ));

            } else {
                array_push($organization[$id]['servers'], array(
                    'id' => $server['server_id'],
                    'name' => $server['server_name'],
                ));
            }

        }, $servers->toArray());

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
