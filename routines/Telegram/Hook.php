<?php

namespace DBRoutines\Telegram;

use DBSnoop\Controller\Lang;
use DBSnoop\Annotations\Active;
use DBSnoop\Annotations\Cron;
use DBSnoop\Annotations\Interval;
use DBSnoop\Annotations\StartRunning;
use DBSnoop\Controller\Telegram;

use DBSnoop\Extension\User as EUser;
use DBSnoop\Entity\User;
use DBSnoop\Lists\ServersStatus;
use DBSnoop\System\Cache;

class Hook{
    
    private $available_methods;

    private $available_commands;
    
    function __construct()
    {
        $this->available_methods = get_class_methods($this);
        $this->available_commands =  array(
            array(
                "command" => "help",
                "description" => "Get help about how to use the bot"
            ),
            array(
                "command" => "registerinfo",
                "description" => "Check your account registration info"
            ),
            array(
                //FEITO
                "command" => "get_servers_error",
                "description" => "Get all alerts and errors found"
            ),
            array(
                //FEITO
                "command" => "get_servers_alert",
                "description" => "Get all alerts found"
            ),
            array(
                "command" => "get_all_status",
                "description" => "Get a status report from all servers"
            ),
            array(
                "command" => "get_status_maintenance",
                "description" => "Get a status report from all servers"
            ),
        );

    }

    /**
     * @Active
     * @StartRunning
     */
    public function configureTelegram()
    {

        Telegram::setCommands($this->available_commands);
        
    }

    /**
     * @Active
     * @Interval(1)
     */
    public function hook()
    {   
        $cache = new Cache;
        $last_id = $cache->get('last_update_telegram_id');
        if(!$last_id)
            $last_id = 0;
        $updates = json_decode(Telegram::sendToTelegram('/getUpdates', array(
            'offset' => $last_id + 1
        )),true);
        

        if(isset($updates['ok']) && $updates['ok']){
            $last = end($updates['result']);
            if($last){
                $cache->set('last_update_telegram_id', $last['update_id'],0);
            }
            foreach($updates['result'] as $messages){
                $messages = $messages['message'];
                $chat = $messages['from']['id'];
                $check_user = EUser::getUserByTelegram($chat);
                if($check_user){
                    $user = new User($check_user['user_id']);

                    $method = "process". strtoupper(substr($messages['text'], 1, 1)) . substr($messages['text'], 2);

                    if(in_array($method, $this->available_methods))
                    {
                        $response = self::$method($user, $messages);
                        if(is_array($response)){
                            foreach($response as $msg){
                                // echo "MESSAGE" . PHP_EOL;
                                // echo $msg . PHP_EOL;
                                Telegram::sendMessage($chat , $msg);
                            }
                        }else{
                            Telegram::sendMessage($chat , $response);
                        }
                    }else{
                        Telegram::sendMessage($chat , "INVALID METHOD!");
                    }
                }else{
                    Telegram::sendMessage($chat , "INVALID USER!");
                }
            }
        }
    }

    public function processHelp()
    {
        $text = "";

        $contador =  count($this->available_commands);
        $value_array = $this->available_commands;
        $text .= "DBSnoop is a database monitoring tool. You can check out more about DBSnoop at http://www.dbsnoop.com/. This bot was created so  you can get alerts on the go, and request reports about you server's health. You need to link your Telegram account to a DBSnoop account. You can use /gettingstarted for this.\n\n";
        $text .= "Other commands that you can use are:\n\n";
        for ($i=0; $i < $contador; $i++) { 
        $text .= "/".$value_array[$i]['command'] . " - " . $value_array[$i]['description'] . "\n";
        }

        return $text;
    }

    public function processGet_servers_error($user)
    {
            $lang  = new Lang();
            $lang->setLang($user->getPreferred_language());

            $filter = array(
                'user' => $user->getId()
            );
            $server_list = new ServersStatus($filter);
            $list = [];
            $array = [];
            $limit = 3500;
            $current_text = '';
            
            foreach($server_list->toArray() as $server){
                if($server['status'] == 'R'){
                    if(!is_array($array[$server['server_name']])) $array[$server['server_name']] = [];
                        // array_push($array[$server['server_name']], $lang($server['alerts'],true));
                        array_push($array[$server['server_name']], $server['alerts']);
                        array_push($array[$server['server_name']], $server['status']);
                        array_push($array[$server['server_name']], $server['customer_name']);
                }
            }
            foreach ($array as $key2 => $value2) {

            $icon = "🚨";
            $text = '';
            $value_alert = str_replace(";", "\n", $value2[0]);
            $text .= " $icon  $key2 ('".$value2[2]."') \n";
            $text .= "$value_alert\n\n";
            

            if(strlen($current_text) + strlen($text) >=$limit){
                $list[] = $current_text;
                $current_text = $text;
            }else{
                $current_text .= $text;
            }
        }
        $list[] = $current_text;

        return $list;
    }




    public function processGet_servers_alert($user)
    {
                 $filter = array(
                'user' => $user->getId()
            );
            $lang  = new Lang();
            $lang->setLang($user->getPreferred_language());
            $server_list = new ServersStatus($filter);
            $list = [];
            $array = [];
            
            foreach($server_list->toArray() as $server){
                if($server['status'] == 'Y'){
                    if(!is_array($array[$server['server_name']])) $array[$server['server_name']] = [];
                        // array_push($array[$server['server_name']], $lang($server['alerts']));
                        array_push($array[$server['server_name']], $server['alerts']);
                        array_push($array[$server['server_name']], $server['status']);
                        array_push($array[$server['server_name']], $server['customer_name']);
                }
            }
            $current_text = "";            
            $limit = 3500;
            foreach ($array as $key2 => $value2) {
                $text = "";
                $icon = "⚠";
                $value_alert = str_replace(";", "\n", $value2[0]);
                $text .= " $icon  $key2 ('".$value2[2]."') \n";
                $text .= "$value_alert\n\n";

                if(strlen($current_text) + strlen($text) >=$limit){
                    $list[] = $current_text;
                    $current_text = $text;
                }else{
                    $current_text .= $text;
                }
            }
            $list[] = $current_text;

            return $list;
    }


    public function processGet_status_maintenance($user)
    {
                 $filter = array(
                'user' => $user->getId()
            );
            $lang  = new Lang();
            $lang->setLang($user->getPreferred_language());
            $server_list = new ServersStatus($filter);
            $list = [];
            $array = [];
            
            foreach($server_list->toArray() as $server){
                if($server['status'] == 'B'){
                    if(!is_array($array[$server['server_name']])) $array[$server['server_name']] = [];
                        array_push($array[$server['server_name']], $server['alerts']);
                        array_push($array[$server['server_name']], $server['status']);
                        array_push($array[$server['server_name']], $server['customer_name']);
                }
            }
            $current_text = "";            
            $limit = 3500;
            foreach ($array as $key2 => $value2) {
                $text = "";
                $icon = "🔵";
                $text .= " $icon  $key2 ('".$value2[2]."') \n";
                
                $text .= "  $value2[0]  \n\n\n";

                if(strlen($current_text) + strlen($text) >=$limit){
                    $list[] = $current_text;
                    $current_text = $text;
                }else{
                    $current_text .= $text;
                }
            }
            $list[] = $current_text;

            return $list;
    }
        
    public function processGet_all_status($user)
    {
             $filter = array(
                'user' => $user->getId()
            );

            $lang  = new Lang();
            $lang->setLang($user->getPreferred_language());
            $server_list = new ServersStatus($filter);
            $list = [];
            $array = [];
            $current_text = "";
            $limit = 3500;        

            foreach($server_list->toArray() as $server){
                 if($server['status'] != 'N'){
                    if(!is_array($array[$server['server_name']])) $array[$server['server_name']] = [];
                        //array_push($array[$server['server_name']], $lang($server['alerts']));
                        array_push($array[$server['server_name']], $server['alerts']);
                        array_push($array[$server['server_name']], $server['status']);
                        array_push($array[$server['server_name']], $server['customer_name']);
                }
            }
            foreach ($array as $key2 => $value2) {
                $icon='';
                $text = "";
                switch ($value2[1]){
                    case 'R':
                        $icon = "🚨";
                        break;
                    case 'Y':
                        $icon = "⚠";
                        break;
                    case 'B':
                        $icon = "🔵";
                        break;
                    case 'G':
                        $icon = "✅";
                }
                
            $value_alert = str_replace(";", "\n", $value2[0]);
            $text .= " $icon  $key2 ('".$value2[2]."') \n";
            $text .= "$value_alert \n\n";
            // if($value2[0] != " " || $value2[0] == NULL){
            //     $text .= "  $value2[0]  \n\n\n";
            // }else{
            //     $text .= " - $value2[0]  \n\n\n";
            // }
            
            if(strlen($current_text) + strlen($text) >=$limit){
                $list[] = $current_text;
                $current_text = $text;
            }else{
                $current_text .= $text;
            }
        }
        $list[] = $current_text;

        return $list;
    }

}