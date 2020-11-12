<?php

namespace DBModules\Servers;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entity\Customer;
use DBSnoop\Entity\Group;
use DBSnoop\Entity\Server;
use DBSnoop\Entity\User;
use DBSnoop\Extension\Server as ExtensionServer;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Servers extends ServerRequestControl
{

    /**
     *
     * @Route("/server/session/new")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "customer",
     *  "group",
     *  "db_type",
     *  "so_type"
     *
     * })
     *
     */
    public function create_session_server(): Response\JSON
    {
        try {

            $group = null;
            $customer = null;
            $user = new User($this->SESSION['user_id']);

            if (empty($this->REQUEST['customer']) || !is_numeric($this->REQUEST['customer'])) {
                return new Response\JSON("error", "INVALID_CUSTOMER");
            } else {
                $customer = new Customer($this->REQUEST['customer'], $user);
            }

            if (!empty($this->REQUEST['group']) && !is_numeric($this->REQUEST['group'])) {
                return new Response\JSON("error", "INVALID_GROUP");
            } else {
                $group = new Group($this->REQUEST['group'], $user);
            }

            $server = new Server(new User($this->SESSION['user_id']));

            if ($customer) {
                $server->customer = $customer;
            }

            if ($group) {
                $server->group = $group;
            }

            if (!empty($this->REQUEST['so_type'])) {
                $server->so_type = $this->REQUEST['so_type'];
            }
            if (!empty($this->REQUEST['db_type'])) {
                $server->db_type = $this->REQUEST['db_type'];
            }

            $hash = ExtensionServer::startNewServerSession($server);

            if (is_array($hash)) {
                return new Response\JSON($hash['status'], $hash['msg']);
            }

            return new Response\JSON('ok', $hash);

        } catch (\Throwable $e) {
            return new Response\JSON("error", $e->getMessage());
        }

    }

    /**
     *
     * @Route("/server/session/load")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "hash"
     * })
     *
     */
    public function teste(): Response\JSON
    {
        try {
            $server_c = ExtensionServer::getServerInSession($this->REQUEST['hash']);

            return new Response\JSON("ok", $server_c->toArray());
        } catch (\Throwable $e) {
            return new Response\JSON("error", $e->getMessage());
        }
    }

    /**
     *
     * @Route("/server/session/configure_db")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "hash",
     *  "host",
     *  "port"
     * })
     *
     */
    public function configureDBServer(): Response\JSON
    {
        try {

            $return = ExtensionServer::addDBConfigSessionServer($this->REQUEST['hash'], $this->REQUEST['host'], $this->REQUEST['port']);

            if (is_array($return)) {
                return new Response\JSON($return['status'], $return['msg']);
            }
            return new Response\JSON("ok", "ok");
        } catch (\Throwable $e) {
            return new Response\JSON("error", $e->getMessage());
        }
    }

    /**
     *
     * @Route("/server/session/configure_so")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     *  "hash",
     *  "host",
     *  "port"
     * })
     *
     */
    public function configureSOServer(): Response\JSON
    {
        try {

            $return = ExtensionServer::addSOConfigSessionServer($this->REQUEST['hash'], $this->REQUEST['host'], $this->REQUEST['port']);

            if (is_array($return)) {
                return new Response\JSON($return['status'], $return['msg']);
            }
            return new Response\JSON("ok", "ok");
        } catch (\Throwable $e) {
            return new Response\JSON("error", $e->getMessage());
        }
    }

    /**
     *
     * @Route("/server/session/get_tutorial_so")
     * @Auth(true)
     * @Type("FILE")
     * @Request("GET")
     * @Needed({
     *  "hash"
     * })
     *
     */
    public function downloadTutorialSO(): Response\File
    {

        $return = ExtensionServer::getTutorialSOSessionServer($this->REQUEST['hash']);

        if (is_array($return)) {
            return new Response\JSON($return['status'], $return['msg']);
        }
        return new Response\FILE("dbsnoop_install.sh", $return);

    }

    /**
     *
     * @Route("/server/session/get_tutorial_db")
     * @Auth(true)
     * @Type("FILE")
     * @Request("GET")
     * @Needed({
     *  "hash"
     * })
     *
     */
    public function downloadTutorialDB(): Response\File
    {

        $return = ExtensionServer::getTutorialDBSessionServer($this->REQUEST['hash']);
        if (is_array($return)) {

            return new Response\JSON($return['status'], $return['msg']);
        }
        return new Response\FILE("dbsnoop_install.sql", $return);
    }
}
