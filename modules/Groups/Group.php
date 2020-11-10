<?php

namespace DBModules\Group;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entity\Customer;
use DBSnoop\Entity\Group;
use DBSnoop\Entity\User;
use DBSnoop\System\ServerRequestControl;
use DBSnoop\System\Response;
use DBSnoop\System\Authentication;

class Groups extends ServerRequestControl
{
    /**
     * 
     * @Route("/groups/new")
     * @Auth(true)
     * @Request("POST")
     * @Type("JSON")
     * @Needed({
     * "customre",
     * "name",
     * "priority"
     * })
     */
    public function new_group(): Response\JSON
    {
        $field_customer = $this->REQUEST['customer'];
        $field_name = $this->REQUEST['name'];
        $field_priority = $this->REQUEST['priority'];
        $field_priority = 1;

        if(!is_numeric($field_customer)){
            return new Response\JSON("error", "INVALID_VALUE_CUSTOMER");
        }

        if(!is_numeric($field_priority)){
            return new Response\JSON("error", "INVALID_VALUE_PRIORITY");
        }

        try {
            
        $user = new User($this->SESSION['user_id']);
        $group = new Group($user);
        $group->customer = new Customer($group, $user);
        $group->name = $field_name;
        $group->priority = $field_priority;

        var_dump($group->save());

        return new Response\JSON("ok", "ok");
        } catch (\Throwable $th) {
            return new Response\JSON("error", $th->getMessage());
        }

    }



    /**
     * 
     * @Route("/groups/update")
     * @Auth(true)
     * @Request("GET")
     * @Type("JSON")
     * @Needed({
     * "field",
     * "value",
     * "id"
     * })
     */
    public function update_group(): Response\JSON
    {
        $array_field = array('name', 'priority');
        $field = $this->REQUEST['field'];

        if (!in_array($field, $array_field)) {
            return new Response\JSON("ok", "INVALID_FIELD");
        }

        try {
            $user = new User($this->SESSION['user_id']);
            $group_id = $this->REQUEST['id'];
            $field_value = $field;
            $value = $this->REQUEST['value'];


            $group = new Group($user, $group_id);

            $group->{$field_value} = $value;
            
    
        return new Response\JSON("ok", "ok");
        } catch (\Throwable $th) {
            return new Response\JSON("error", $th->getMessage());
        }

    }
}
