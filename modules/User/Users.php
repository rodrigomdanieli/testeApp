<?php

namespace DBModules\User;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entity\Customer;
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

    /** 
     * @Route("/user/new_user")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "name",
     * "customer",
     * "abbr",
     * "email",
     * "phone",
     * "type",
     * "team_level",
     * "preferred_language",
     * "timezone"
     * })
     */
    public function new_user(): Response\JSON
    {
        $array_language = array('pt-br', 'en-us', 'es-es');
        $language = $this->REQUEST['preferred_languages'];
        $name = $this->REQUEST['name'];
        $type = $this->REQUEST['type'];
        $team_level = $this->REQUEST['team_level'];
        $customer_field = $this->REQUEST['customer'];
        $customer_field = $this->SESSION['customer_id'];
        $abbr_field = $this->REQUEST['abbr'];
        $timezone_field = $this->REQUEST['timezone'];
        $email_field = $this->REQUEST['email'];

        if(empty($language)){
            $language = 'en-us';
        }

        if(!array_search($language, $array_language)){
            return new Response\JSON("error", "INVALID_LANGUAGE");
        }

        if(empty($timezone_field)){
            $timezone_field = '+00:00';
        }

        if(empty($abbr_field)){
            preg_match_all('/\b\w/u', $name, $m);
            $abbr_field = implode('',$m[0]);
        }

        if(empty($name)){
            return new Response\JSON("error", "EMPTY_NAME");
        }

        if(empty($type)){
            return new Response\JSON("error", "EMPTY_TYPE");
        }

        if(empty($team_level)){
            return new Response\JSON("error", "EMPTY_TEAM_LEVEL");
        }

        if(empty($email_field)){
            return new Response\JSON("error", "EMPTY_NEW_PASSWORD");
        }

        if(!is_numeric($customer_field)){
            return new Response\JSON("error", "INVALID_CUSTOMER");
        }

        $obj_user = new User( new User($this->SESSION['user_id']));
        $obj_user->name = $name;
        $obj_user->customer =  new Customer($customer_field,new User($this->SESSION['user_id']));
        $obj_user->abbr = $abbr_field;
        $obj_user->email = $email_field;
        $obj_user->telephone = $this->REQUEST['phone'];
        $obj_user->type = $type;
        $obj_user->team_level = $team_level;
        $obj_user->preferred_language = $language;
        $obj_user->timezone = $timezone_field;

        var_dump($obj_user->save());
        
        return new Response\JSON("OK", "OK");

    }


    /** 
     * @Route("/user/update")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "field",
     * "value",
     * "id"
     * })
     */
    public function update_user(): Response\JSON
    {
        $array_field = array("name", "abbr", "email", "phone", 
        "type","team_level", "preferred_language","timezone" );
        $value_field = $this->REQUEST['field'];
        $value_id = $this->REQUEST['id'];
        $value = $this->REQUEST['value'];

        if(!array_search($value_field, $array_field)){
            return new Response\JSON("error", "INVAID_FIELD");
        }

        if(empty($value_field)){
            return new Response\JSON("error", "EMPTY_FIELD");
        }

        if(empty($value_id)){
            return new Response\JSON("error", "EMPTY_VALUE_ID");
        }

        if(empty($value)){
            return new Response\JSON("error", "EMPTY_VALUE");
        }

        $user = new User(new User($this->SESSION['user_id']),$value_id);
        $user->{$value_field} = $value;

        var_dump($user->save());

        return new Response\JSON("OK", "OK");

    }

    /** 
     * @Route("/user/set_preference")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "preference",
     * "value"
     * })
     */
    public function set_user(): Response\JSON
    {
        $preference_field = $this->REQUEST['preference'];
        $value_field = $this->REQUEST['value'];

        if(empty($preference_field)){
            return new Response\JSON("error", "EMPTY_PREFERENCE");
        }
        
        if(empty($value_field)){
            return new Response\JSON("error", "EMPTY_VALUE");
        }

        $user_session = new User($this->SESSION['user_id'], new User($this->SESSION['user_id']));
        $user = new ExtensionUser($user_session);

        $user->setPreference($preference_field, $value_field);


        return new Response\JSON("ok", "ok");

    }


    /** 
     * @Route("/user/get_preference")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     */
    public function get_user(): Response\JSON
    {

        $user_session = new User($this->SESSION['user_id']);
        $user = new ExtensionUser($user_session);


        return new Response\JSON("ok", $user->getPreferences());

    }

}
