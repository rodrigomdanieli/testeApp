<?php

namespace DBModules\Logbook;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\System\ServerRequestControl;
use DBSnoop\System\Response;
use DBSnoop\Extension\Customer as ExtensionCustomer;
use DBSnoop\Entity\Customer as EntityCustomer;
use DBSnoop\Entity\User;
use DBSnoop\Entity\LogbookEntry as EntryLogBook;
use DBSnoop\Lists\Logbook as ListLogBook;
use DBSnoop\Entity\Logbook as LogBooks;
use DBSnoop\Entity\Ticket;
use DBSnoop\Extension\Logbook as ExtensionLogBook;





class Logbook extends ServerRequestControl
{
    /**
     *
     * @Route("/logbook/new")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "customer",
     * "type",
     * "severity",
     * "unavailability",
     * "unavailability_time",
     * "reported",
     * "thread_closed",
     * "title",
     * "description"
     * })
     */
    public function new_logbook(): Response\JSON
    {
        $array_type = array('Incident','Problem','Recommendation','SQL','Performance','Maintenance','Monitoring','Infrastructure','Application','Report','Log');
        $type = $this->REQUEST['type'];
        $array_severity = array('High','Medium','Low');
        $severity = $this->REQUEST['severity'];

        $customer = $this->REQUEST['customer'];
        $unavailability_time = $this->REQUEST['unavailability_time'];

        if(!in_array($type, $array_type)){
            return new Response\JSON("error", "INVALID_TYPE");
        }
    
        if(!in_array($severity, $array_severity)){
            return new Response\JSON("error", "INVALID_SEVERITY");
        }

        if(!is_numeric($customer)){
            return new Response\JSON("error", "INVALID_CUSTOMER");
        }
        
        if(!empty($unavailability_time) && !is_numeric($unavailability_time)){
            return new Response\JSON("error", "INVALID_UNAVAILABILITY_TIME");
        }

        $user = new User($this->SESSION['user_id']);

        
        $logBook = new LogBooks($user);
        $logBook->customer = new EntityCustomer($customer);
        $logBook->type = $type;
        $logBook->severity = $severity;
        $logBook->unavailability = $this->REQUEST['unavailability'];
        $logBook->unavailability_time = $unavailability_time;
        $logBook->reported = $this->REQUEST['reported'];
        $logBook->thread_closed = $this->REQUEST['thread_closed'];
        $logBook->title = $this->REQUEST['title'];
        $logBook->description = $this->REQUEST['description'];

        $save = $logBook->save();
        
        return new Response\JSON("ok", $save['data']);        
    }

    /**
     *
     * @Route("/logbook/update")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "field",
     * "value",
     * "id"
     * })
     */
    public function update_logbook(): Response\JSON
    {
        $array_field = array("customer","type","severity","unavailability","unavailability_time","reported","thread_closed","title","description");
        $field = $this->REQUEST['field'];

        if(!in_array($field,$array_field)){
            return new Response\JSON("error", "INVALID_FIELD");
        }

        $user = new User($this->SESSION['user_id']);
        $logBook_id = $this->REQUEST['id'];
        $field_value = $field;
        $value = $this->REQUEST['value'];

        $logBook = new LogBooks($logBook_id, $user);
        $logBook->{$field_value} = $value;
        $logBook->save();

        
        return new Response\JSON("ok", "OK");
        
    }

    /**
     *
     * @Route("/logbook/new_entry")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "logbook",
     * "entry",
     * "user"
     * })
     */
    public function new_logbookEntry(): Response\JSON
    {
        $logBook = $this->REQUEST['logbook'];
        if(!is_numeric($logBook)){
            return new Response\JSON("error", "INVALID_LOGBOOK");
        }

        $user_obj = new User();
        $logBook_Entry = new EntryLogBook($user_obj);
        $logBook_Entry->logbook = $logBook;
        $logBook_Entry->entry = $this->REQUEST['entry'];
        $logBook_Entry->user = $user_obj;

        $logBook_Entry->save();


        return new Response\JSON("ok", "OK");
        
    }


    /**
     *
     * @Route("/logbook/get_tickets")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "id"
     * })
     */
    public function get_tickets_logbook(): Response\JSON
    {

        $user = new User($this->SESSION['user_id']);
        $logBook = new LogBooks($this->REQUEST['id'],$user);
        $logBookList = new ExtensionLogBook($logBook);
        $list = $logBookList->getTicketList();

        return new Response\JSON("ok", $list['data']);
        
    }



    /**
     *
     * @Route("/logbook/add_ticket")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "ticket_id",
     * "id"
     * })
     */
    public function add_ticket_logbook(): Response\JSON
    {
        $user = new User($this->SESSION['user_id']);
        $logBook = new LogBooks($this->REQUEST['id'],$user);
        $logBookList = new ExtensionLogBook($logBook);
        $logBookList->addTicket(new Ticket($this->REQUEST['ticket_id']));

        return new Response\JSON("ok", "OK");
        
    }

    

    /**
     *
     * @Route("/logbook/list")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "customer"
     * })
     */
    public function list_logbook(): Response\JSON
    {
        $filter = array(
            "customer" => $this->REQUEST['customer'],
            "user"  => $this->SESSION['user_id']
        );

        $log = new ListLogBook($filter);

        return new Response\JSON("ok", $log->toArray());
        
    }

    /**
     *
     * @Route("/logbook/get")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "id"
     * })
     */
    public function get_logbook(): Response\JSON
    {

        $log = new LogBooks(new User($this->SESSION['user_id']), $this->REQUEST['id']);
        
        return new Response\JSON("ok", $log->toArray());
        
    }

    /**
     *
     * @Route("/logbook/closed")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "id"
     * })
     */
    public function closed_logbook(): Response\JSON
    {

        $log = new LogBooks(new User($this->SESSION['user_id']), $this->REQUEST['id']);
        $log->timestamp_close = date('Y-m-d h:i:sa');
        $log->save();

        
        return new Response\JSON("ok", "OK");
        
    }
}
