<?php

namespace DBRoutines\Telegram;

use DBSnoop\Annotations\Active;
use DBSnoop\Annotations\Cron;
use DBSnoop\Annotations\Interval;
use DBSnoop\Annotations\StartRunning;
use DBSnoop\Controller\Telegram;


class Start{


    /**
     * @Active
     * @StartRunning
     */
    public function configureTelegram()
    {

        $commands = array(
            array(
                "command" => "help",
                "description" => "Get help about how to use the bot"
            ),
            array(
                "command" => "registerinfo",
                "description" => "Check your account registration info"
            ),
            array(
                "command" => "get_servers_error",
                "description" => "Get all alerts and errors found"
            ),
            array(
                "command" => "get_servers_alert",
                "description" => "Get all alerts found"
            ),
            array(
                "command" => "get_status_server",
                "description" => "Get a status report from all servers"
            )
        );

        Telegram::setCommands($commands);
        
        Telegram::sendToTelegram("/setWebhook", array(
            "url" => "https://vikings.hti.com.br/api/telegram/hook"
        ));

    }

}