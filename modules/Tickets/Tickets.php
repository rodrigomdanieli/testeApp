<?php

namespace DBModules\Tickets;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entity\Customer;
use DBSnoop\Entity\Handshake;
use DBSnoop\Entity\Server;
use DBSnoop\Entity\Ticket;
use DBSnoop\Entity\User;
use DBSnoop\Extension\Ticket as ExtensionTicket;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Tickets extends ServerRequestControl
{

    /**
     *
     * @Route("/ticket/check_authorized")
     * @Auth(true)
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
     * @Auth(true)
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
        
        if (!empty($this->REQUEST['customer'])) {
            return new Response\JSON("error", "EMPTY_CUSTOMER_FIELD");
        }

        if (!empty($this->REQUEST['server'])) {
            if (!is_numeric($this->REQUEST['server'])) {
                return new Response\JSON("error", "INVALID_SERVER");
            } else {

                $server = new Server($this->REQUEST['server']);

                if (!\DBSnoop\Extension\Server::checkService($server, $this->REQUEST['service'])) {
                    return new Response\JSON("error", "INVALID_SERVICE");
                }
            }
        }

        // if (!is_numeric($this->REQUEST['solicited'])) {
        //     return new Response\JSON("error", "INTERNAL_ERROR");
        // }

        if (!in_array($this->REQUEST['area'], $array_area)) {
            return new Response\JSON("error", "INVALID_AREA");
        }

        if (is_numeric($this->REQUEST['service'])) {
            return new Response\JSON("error", "INVALID_SERVICE");
        }

        if (!is_numeric($this->REQUEST['err_code'])) {
            return new Response\JSON("error", "INVALID_ERR_CODE");
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

        if ($this->REQUEST['type'] == "Incident") {
            if (!empty($this->REQUEST['handshake'])) {
                if (!is_numeric($this->REQUEST['handshake'])) {
                    return new Response\JSON("error", "INVALID_HANDSHAKE");
                }
            }
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

            $save = $tkt->save();
            return new Response\JSON("ok", $save['data']);

        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("ok", $th->getMessage());
        } catch(\Throwable $e){
            return new Response\JSON('error', $e->getMessage());
        }


    }

    /**
     *
     * @Route("/ticket/update")
     * @Auth(true)
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

        if (!in_array($this->REQUEST['field'], $array_field)) {
            return new Response\JSON("ok", "INVALID_FIELD");
        }

        if (!is_string($this->REQUEST['value'])) {
            return new Response\JSON("ok", "INVALID_VALUE");
        }
        if (!is_numeric($this->REQUEST['id'])) {
            return new Response\JSON("ok", "INVALID_VALUE");
        }

        if ($this->REQUEST['field'] === 'solicited') {
            $value = new User($this->REQUEST['value']);
        }
        if ($this->REQUEST['field'] === 'team_level') {
            if (!\DBSnoop\Extension\Ticket::checkTeamLevel($this->REQUEST['value']))
            // $value = $this->REQUEST['value'];
            {
                return new Response\JSON("ok", "INVALID_TEAM_LEVEL");
            }

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
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "id",
     * "status"
     * })
     */
    public function change_status_ticket(): Response\JSON
    {

        if (!is_numeric($this->REQUEST['id'])) {
            return new Response\JSON("ok", "INVALID_ID");
        }

        if (!is_string($this->REQUEST['status'])) {
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
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "master",
     * "to_merge"
     * })
     */
    public function merge_ticket(): Response\JSON
    {

        if (!is_numeric($this->REQUEST['master'])) {
            return new Response\JSON("ok", "INVALID_MASTER");
        }

        if (!is_numeric($this->REQUEST['to_merge'])) {
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
        } catch (\Throwable $th) {
            return new Response\JSON("ok", $th->getMessage());
        }

    }

    /**
     *
     * @Route("/ticket/get_comments")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "id"
     * })
     */
    public function ticket_commentary(): Response\JSON
    {
        if (!is_numeric($this->REQUEST['id'])) {
            return new Response\JSON("ok", "INVALID_ID");
        }

        try {
            $user_id = new User($this->SESSION['user_id']);
            $ticket_id = $this->REQUEST['id'];
            $tkt = new Ticket($ticket_id, $user_id);
            $commentary['comments'] = [];

            foreach ($tkt->getTicket_commentary() as $key => $value_comment) {
                $commentary['comments'][$key] = $value_comment->toArray();
                //$commentary['commentary'][$key]['files'] = array();
                //var_dump($commentary['commentary'][$key]['files']);

                foreach ($value_comment->getFiles() as $file) {
                    array_push($commentary['comments'][$key]['files'], $file->toArray());
                }
            }

            // var_dump($commentary);

            return new Response\JSON("ok", $commentary);
        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("ok", $th->getMessage());
        } catch (\Throwable $th) {
            return new Response\JSON("ok", $th->getMessage());
        }

    }

    /**
     *
     * @Route("/ticket/get")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "id"
     * })
     */
    public function get_ticket(): Response\JSON
    {
        if (!is_numeric($this->REQUEST['id'])) {
            return new Response\JSON("ok", "INVALID_ID");
        }

        try {
            $user_id = new User($this->SESSION['user_id']);
            $ticket_id = $this->REQUEST['id'];
            $tkt = new Ticket($ticket_id, $user_id);

            return new Response\JSON("ok", $tkt->toArray());
        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("ok", $th->getMessage());
        } catch (\Throwable $th) {
            return new Response\JSON("ok", $th->getMessage());
        }

    }

    /**
     *
     * @Route("/ticket/get_issues_domain")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     */
    public function get_issues_domain(): Response\JSON
    {

        try {
            $ext = ExtensionTicket::getIssuesDomain();
            return new Response\JSON("ok", $ext['data']);
        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("ok", $th->getMessage());
        } catch (\Throwable $th) {
            return new Response\JSON("ok", $th->getMessage());
        }

    }

    /**
     *
     * @Route("/ticket/get_available_functions")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "id"
     * })
     */
    public function get_available_functions(): Response\JSON
    {
        $tkt = new Ticket($this->REQUEST['id'], new User($this->SESSION['user_id']));
        $ext = new ExtensionTicket($tkt);
        $functions = $ext->getAvailableFunctions();
        return new Response\JSON($functions['status'], $functions['data']);
    }

}
