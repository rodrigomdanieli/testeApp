<?php

    require 'vendor/autoload.php';

    use DBSnoop\System\DBSnoop;
    $server = new DBSnoop($argv);
    $server->run();


