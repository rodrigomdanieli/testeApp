<?php

namespace DBModules\AutoLists;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entities\Lists as ELists;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Lists extends ServerRequestControl
{

    /**
     *
     * @Route("/autolist/availableServers")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "customer",
     *  "group",
     *  "db_type",
     *  "replica",
     *  "only_available",
     *  "removed"
     * })
     */
    public function availableServers(): Response\JSON
    {

        $servers = new ELists\Server($this->SESSION['user_id']);
        $list = $servers->byFilter($this->REQUEST);

        if ($list['status'] == "ok") {
            $data = array_map(function ($server) {
                return array(
                    "id"        => $server['server_id'], 
                    "name"      => $server['server_name'],
                    "filter"    => array(
                        "customer_id"   => $server['customer_id'],
                        "group_id"      => $server['group_id']
                    )
                );
            }, $list['data']);

            return new Response\JSON("ok", $data);

        } else {
            return new Response\JSON("error", $list);
        }
    }

    /**
     *
     * @Route("/autolist/availableGroups")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     */
    public function availableGroups(): Response\JSON
    {

        $group = new ELists\Group($this->SESSION['user_id']);
        $list = $group->getAll();

        if ($list['status'] == "ok") {
            $data = array_map(function ($group) {
                if (is_null($group['group_id'])) {
                    return ;
                }

                return array(
                    "id"        => $group['group_id'],
                    "name"      => $group['group_name'],
                    "filter"    => array(
                        "customer_id" => $group['customer_id']
                    ));
            }, $list['data']);

            return new Response\JSON("ok", $data);

        } else {
            return new Response\JSON("error", $list);
        }
    }

    /**
     *
     * @Route("/autolist/availableCustomers")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     */
    public function availableCustomers(): Response\JSON
    {
        $group = new ELists\Customer($this->SESSION['user_id']);
        $list = $group->getAll();

        if ($list['status'] == "ok") {
            $data = array_map(function ($customer) {
                return array(
                    "id"        => $customer['customer_id'],
                    "name"      => $customer['customer_name']
                );
            }, $list['data']);

            return new Response\JSON("ok", $data);

        } else {
            return new Response\JSON("error", $list);
        }
    }
}
