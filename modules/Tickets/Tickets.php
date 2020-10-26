<?php

namespace DBModules\Tickets;

use DBSnoop\Entity\Customer;
use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entity\Handshake;
use DBSnoop\Entity\Server;
use DBSnoop\Entity\Ticket;
use DBSnoop\Entity\User;
use DBSnoop\Extension\Ticket as ExtensionTicket;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;
use DBSnoop\Extension\Server as ExtenionServer;

class Tickets extends ServerRequestControl
{

    /**
     *
     * @Route("/ticket/check_authorized")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "id"
     * })
     */
    public function checkAuthorizedTicket(): Response\JSON
    {
        $ticket = new Ticket($this->REQUEST['id'], new User($this->SESSION['user_id']));
        return new Response\JSON("ok", array("authorized" => $ticket->checkAuth()));

    }


    /**
     *
     * @Route("/ticket/new")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "customer",
     * "server",
     * "handshake",
     * "area",
     * "service",
     * "err_code",
     * "err_info",
     * "team_level",
     * "priority",
     * "type"
     * })
     */
    public function new_ticket(): Response\JSON
    {

        $array_area = array('Monitoring', 'Operation', 'Security', 'Infrastructure', 'SQL', 'Architecture', 'Governance');
        $array_priority = array('Low', 'Medium', 'High', 'Critical');
        $array_type = array('Incident', 'Request', 'Emergency', 'Maintenance', 'Email', 'Monitoring', 'Coordination');
        $server = new Server;

        if (!is_numeric($this->REQUEST['customer'])) {
            return new Response\JSON("error", "INVALID_CUSTOMER");
        }

        if (!is_numeric($this->REQUEST['server'])) {
            return new Response\JSON("error", "INVALID_SERVER");
        }else{

            $server = new Server($this->REQUEST['server']);

            if (!\DBSnoop\Extension\Server::checkService($server, $this->REQUEST['service'])) {
                return new Response\JSON("error", "INVALID_SERVICE");
            }
        }

        // if (!is_numeric($this->REQUEST['solicited'])) {
        //     return new Response\JSON("error", "INTERNAL_ERROR");
        // }

        if (!is_numeric($this->REQUEST['handshake'])) {
            return new Response\JSON("error", "INVALID_HANDSHAKE");
        }

        if (!in_array($this->REQUEST['area'], $array_area)) {
            return new Response\JSON("error", "INVALID_AREA");
        }

        if (is_numeric($this->REQUEST['service'])) {
            return new Response\JSON("error", "INVALID_SERVICE");
        }

        if (!is_numeric($this->REQUEST['err_code'])) {
            return new Response\JSON("error", "INVALID_ERR_CODE");
        }

        if (is_numeric($this->REQUEST['err_info'])) {
            return new Response\JSON("error", "INVALID_ERR_INFO");
        }

        if (!is_numeric($this->REQUEST['team_level'])) {
            return new Response\JSON("error", "INTERNAL_ERROR");
        }

        if (!\DBSnoop\Extension\Ticket::checkTeamLevel($this->REQUEST['team_level'])) {
            return new Response\JSON("error", "INVALID_TEAM_LEVEL");
        }

        if (!in_array($this->REQUEST['priority'], $array_priority)) {
            return new Response\JSON("error", "INVALID_PRIORITY");
        }

        if (!in_array($this->REQUEST['type'], $array_type)) {
            return new Response\JSON("error", "INVALID_TYPE");
        }



        try {
            $tkt = new Ticket();
            $tkt->customer = new Customer($this->REQUEST['customer']);
            $tkt->server = $server;
            $tkt->user = new User($this->SESSION['user_id']);
            $tkt->handshake = new Handshake($this->REQUEST['handshake']);
            $tkt->area = $this->REQUEST['area'];
            $tkt->service = $this->REQUEST['service'];
            $tkt->err_code = $this->REQUEST['err_code'];
            $tkt->err_info = $this->REQUEST['err_info'];
            $tkt->team_level = $this->REQUEST['team_level'];
            $tkt->priority = $this->REQUEST['priority'];
            $tkt->type = $this->REQUEST['type'];

            var_dump($tkt->save());
            new Response\JSON("ok", "ok");

        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("ok", $th->getMessage());
        }

    }

    /**
     *
     * @Route("/ticket/update")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "field",
     * "value",
     * "id"
     * })
     */
    public function update_ticket(): Response\JSON
    {
        $value = $this->REQUEST['value'];
        $array_field = array('solicited', 'area', 'team_level', 'priority');

        if(!in_array($this->REQUEST['field'], $array_field)){
            return new Response\JSON("ok", "INVALID_FIELD");
        }

        if(!is_string($this->REQUEST['value'])){
            return new Response\JSON("ok", "INVALID_VALUE");
        }
        if(!is_numeric($this->REQUEST['id'])){
            return new Response\JSON("ok", "INVALID_VALUE");
        }
        
        if($this->REQUEST['field'] === 'solicited'){
            $value = new User($this->REQUEST['value']);
        }
        if($this->REQUEST['field'] === 'team_level'){
            if(!\DBSnoop\Extension\Ticket::checkTeamLevel($this->REQUEST['value']))
                // $value = $this->REQUEST['value']; 
                return new Response\JSON("ok", "INVALID_TEAM_LEVEL");
        }

        


        try {

            $user_id = new User($this->SESSION['user_id']);
            $ticket_id = $this->REQUEST['id'];
            $field = $this->REQUEST['field'];
            $value = $value;

            $tkt = new Ticket($ticket_id, $user_id);
            $tkt->{$field} = $value;

            var_dump($tkt->save());
            return new Response\JSON("ok", "ok");
        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("ok", $th->getMessage());
        }

    }



    /**
     *
     * @Route("/ticket/change_status")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "id",
     * "status"
     * })
     */
    public function change_status_ticket(): Response\JSON
    {
   
        if(!is_numeric($this->REQUEST['id'])){
            return new Response\JSON("ok", "INVALID_ID");
        }

        if(!is_string($this->REQUEST['status'])){
            return new Response\JSON("ok", "INVALID_STATUS");
        }
        
        
        try {
            $user_id = new User($this->SESSION['user_id']);
            $ticket_id = $this->REQUEST['id'];
            $tkt = new Ticket($ticket_id, $user_id);
            $extension = new ExtensionTicket($tkt);

            $extension->changeStatus($this->REQUEST['status']);

            return new Response\JSON("ok", $this->REQUEST['status']);
        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("ok", $th->getMessage());
        }

    }


    /**
     *
     * @Route("/ticket/merge_ticket")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "master",
     * "to_merge"
     * })
     */
    public function merge_ticket(): Response\JSON
    {
   
        if(!is_numeric($this->REQUEST['master'])){
            return new Response\JSON("ok", "INVALID_MASTER");
        }

        if(!is_numeric($this->REQUEST['to_merge'])){
            return new Response\JSON("ok", "INVALID_TO_MERGE");
        }
        
        
        try {
            $user_id = new User($this->SESSION['user_id']);
            $ticket_id = $this->REQUEST['master'];
            $tkt = new Ticket($ticket_id, $user_id);
            $extension = new ExtensionTicket($tkt);
            
            $extension->doMerge(new Ticket($this->REQUEST['to_merge'], $user_id));

            return new Response\JSON("ok", "ok");
        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("ok", $th->getMessage());
        }catch (\Throwable $th){
            return new Response\JSON("ok", $th->getMessage());
        }

    }

}
