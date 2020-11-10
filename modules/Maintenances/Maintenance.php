<?php

namespace DBModules\Maintenances;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entity\User;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;
use DBSnoop\Entity\Maintenance as EntityMaintenance;
use DBSnoop\Entity\Server as EntityServer;
use DBSnoop\Lists\Alert as ListsAlert;
use DBSnoop\Extension\Maintenance as ExtensionMaintenance;

class Maintenance extends ServerRequestControl
{
    /**
     *
     * @Route("/maintenance/new")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "server",
     * "start",
     * "end",
     * "recurrent",
     * "reason",
     * "alert",
     * "schema"
     * })
     */
    public function new_maintenance(): Response\JSON
    {
        $array_recurrent = array('No', 'Daily', 'Weekly', 'Monthly', 'Yearly');
        if(empty($this->REQUEST['server'])){
            return new Response\JSON("error", "INVALID_SERVER");
        }
        
        if(!is_numeric($this->REQUEST['server'])){
            return new Response\JSON("error", "INVALID_SERVER");
        }

        if(empty($this->REQUEST['start'])){
            return new Response\JSON("error", "EMPTY_FIELD");
        }

        if(empty($this->REQUEST['recurrent'])){
            return new Response\JSON("error", "EMPTY_FIELD");
        }

        if(empty($this->REQUEST['reason'])){
            return new Response\JSON("error", "EMPTY_FIELD");
        }

        if(!in_array($this->REQUEST['recurrent'], $array_recurrent)){
            return new Response\JSON("error", "INVALID_RECURRENT");
        }

        if(!empty($this->REQUEST['alert']) && !is_numeric($this->REQUEST['alert'])){
            return new Response\JSON("error", "INVALID_ALERT");
        }

        try {
            
            $user = new User($this->SESSION['user_id']);
            $maintenance = new EntityMaintenance($user);
            $maintenance->server = new EntityServer($this->REQUEST['server'], $user);
            $maintenance->start = $this->REQUEST['start'];
            $maintenance->end = $this->REQUEST['end'];
            $maintenance->recurrent = $this->REQUEST['recurrent'];
            $maintenance->reason = $this->REQUEST['reason'];
            $maintenance->alert = $this->REQUEST['alert'];
            $maintenance->schema = $this->REQUEST['schema'];
            $save = $maintenance->save();
            return new Response\JSON("ok", $save['data']);
        } catch (\Throwable $th) {
            return new Response\JSON("error", $th->getMessage());
        }

    }

    /**
     *
     * @Route("/maintenance/update")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "maintenance",
     * "field",
     * "value"
     * })
     */
    public function update_maintenance(): Response\JSON
    {
       $array_field = array("reason");
       $field = $this->REQUEST['field'];
       if(!in_array($field, $array_field)){
            return new Response\JSON("error", "FIELD_WITH_INVALID_VALUE");
       }

        try {
            
            $user = new User($this->SESSION['user_id']);
            $maintenance = new EntityMaintenance($user, $this->REQUEST['maintenance']);
            $maintenance->$field = $this->REQUEST['value'];

            
            var_dump($maintenance->save());

            return new Response\JSON("ok", "ok");
        } catch (\Throwable $th) {
            return new Response\JSON("error", $th->getMessage());
        }

    }



    /**
     *
     * @Route("/maintenance/get")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "maintenance"
     * })
     */
    public function get_maintenance(): Response\JSON
    {
        $field_maintenance = $this->REQUEST['maintenance']; 
        if(!is_numeric($field_maintenance)){
            return new Response\JSON("error", "INVALID_MAINTENANCE");
        }

        try {
            $user = new User($this->SESSION['user_id']);
            $maintenance = new EntityMaintenance($field_maintenance,$user);

            return new Response\JSON("ok", $maintenance->toArray());
        } catch (\Throwable $th) {
            return new Response\JSON("error", $th->getMessage());
        }

    }

    /////-------------------------Acima está tudo certo e testado-----------------------------------------


    /////-------------------------TESTAR ESSA FUNÇÃO NOVAMENTE!!!!!!!-----------------------------------------
    /**
     *
     * @Route("/maintenance/approve")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "maintenance"
     * })
     */
    public function approve_maintenance(): Response\JSON
    {
        $field_maintenance = $this->REQUEST['maintenance'];
        if(empty($field_maintenance)){
            return new Response\JSON("error", "EMPTY_FIELD");
        }

        if(!is_numeric($field_maintenance)){
            return new Response\JSON("error", "INVALID_MAINTENANCE");
        }
        try {
            $user = new User($this->SESSION['user_id']);
            $maintenance = new EntityMaintenance($field_maintenance, $user);
            $extension_maintenance = new ExtensionMaintenance($maintenance);
            $valid_maintenance = $extension_maintenance->approveMaintenance($maintenance);

            var_dump($valid_maintenance);

            return new Response\JSON("ok", "ok");
        } catch (\Throwable $th) {
            return new Response\JSON("error", $th->getMessage());
        }

    }

    /**
     *
     * @Route("/maintenance/remove")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "maintenance"
     * })
     */
    public function remove_maintenance(): Response\JSON
    {
        $field_maintenance = $this->REQUEST['maintenance'];
        if(empty($field_maintenance)){
            return new Response\JSON("error", "EMPTY_FIELD");
        }

        if(!is_numeric($field_maintenance)){
            return new Response\JSON("error", "INVALID_MAINTENANCE");
        }
        try {
            $user = new User($this->SESSION['user_id']);
            $maintenance = new EntityMaintenance($field_maintenance, $user);
            $extension_maintenance = new ExtensionMaintenance($maintenance);
            $valid_maintenance = $extension_maintenance->removeMaintenance($maintenance);

            var_dump($valid_maintenance);

            return new Response\JSON("ok", "ok");
        } catch (\Throwable $th) {
            return new Response\JSON("error", $th->getMessage());
        }

    }


    /**
     *
     * @Route("/maintenance/finish")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "maintenance"
     * })
     */
    public function finish_maintenance(): Response\JSON
    {
        $field_maintenance = $this->REQUEST['maintenance'];
        if(empty($field_maintenance)){
            return new Response\JSON("error", "EMPTY_FIELD");
        }

        if(!is_numeric($field_maintenance)){
            return new Response\JSON("error", "INVALID_MAINTENANCE");
        }
        try {
            $user = new User($this->SESSION['user_id']);
            $maintenance = new EntityMaintenance($field_maintenance, $user);
            $extension_maintenance = new ExtensionMaintenance($maintenance);
            $valid_maintenance = $extension_maintenance->finishMaintenance($maintenance);

            var_dump($valid_maintenance);

            return new Response\JSON("ok", "ok");
        } catch (\Throwable $th) {
            return new Response\JSON("error", $th->getMessage());
        }

    }


    
}
