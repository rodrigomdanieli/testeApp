<?php

namespace DBModules\Tickets;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entity\Ticket;
use DBSnoop\Entity\TicketCommentary as TktCommentary;
use DBSnoop\Entity\User;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class TicketCommentary extends ServerRequestControl
{

    /**
     *
     * @Route("/ticket_commentary/get")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "id"
     * })
     */
    public function getTicketCommentary(): Response\JSON
    {
        try {
            $ticket_commentary = new TktCommentary($this->REQUEST['id']);
            $array = $ticket_commentary->getAllFiles();
            $toArray = array(
                'id' => $ticket_commentary->getId(),
                'ticket_id' => $ticket_commentary->getTicket()->getId(),
                'user_id' => $ticket_commentary->getUser()->getId(),
                'commentary' => $ticket_commentary->getCommentary(),
                'time_used' => $ticket_commentary->getTime_used(),
                'type' => $ticket_commentary->getType(),
                'timestamp' => $ticket_commentary->getTimestamp(),
                'files' => array_map(function ($file) {
                    return array(
                        "id" => $file->getId(),
                        "commentary" => $file->getCommentary()->getId(),
                        "name" => $file->getName(),
                        "ext" => $file->getExt(),
                        "path" => $file->getPath(),
                    );
                }, $array),
            );

            return new Response\JSON("ok", $toArray);
        } catch (\Exception $e) {
            return new Response\JSON("error", $e->getMessage());
        }
    }

    /**
     *
     * @Route("/ticket_commentary/getFiles")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "id"
     * })
     */
    public function getTicketCommentaryFile(): Response\JSON
    {
        try {
            $ticket_commentary = new TktCommentary($this->REQUEST['id'], array('not_load' => true));

            $array = $ticket_commentary->getAllFiles();

            $toArray = array_map(function ($file) {
                return array(
                    "id" => $file->getId(),
                    "commentary" => $file->getCommentary()->getId(),
                    "name" => $file->getName(),
                    "ext" => $file->getExt(),
                    "path" => $file->getPath(),
                );
            }, $array);

            return new Response\JSON("ok", $toArray);
        } catch (\Exception $e) {
            return new Response\JSON("error", $e->getMessage());
        }
    }

    /**
     *
     * @Route("/ticket_commentary/new")
     * @Auth("true")
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "id",
     *  "comment",
     *  "type",
     *  "time_used"
     * })
     */
    public function new_commentary(): Response\JSON
    {
        try {
            $ticket_commentary = new TktCommentary();
            $user = new User($this->SESSION['user_id']);
            $ticket_commentary->user= $user;
            $ticket_commentary->commentary = $this->REQUEST['comment'];
            $ticket_commentary->time_used = $this->REQUEST['time_used'];
            $ticket_commentary->ticket = new Ticket($this->REQUEST['id'], $user);
            $ticket_commentary->type = $this->REQUEST['type'];

            $saved = $ticket_commentary->save();

            if ($saved['status'] == 'ok') {
                return new Response\JSON("ok", "ok");
            } else {
                return new Response\JSON("error", array('msg' => "Error"));
            }

        } catch (\Exception $e) {
            return new Response\JSON("error", $e->getMessage());
        }
    }

}
