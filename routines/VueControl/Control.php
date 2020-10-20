<?php

namespace DBRoutines\VueControl;

use DBSnoop\Annotations\{Active, Interval, StartRunning};
use DBSnoop\SysControl\VueControl;
use DBSnoop\System\Cache;
use DBSnoop\System\Utils;

class Control{

    /**
     * 
     * 
     * @Interval(300)
     * @StartRunning
     */
    public function loadRoutes(){
        
        $vue = new VueControl();
        $cache = new Cache;
        $routes = $vue->getActiverRoutes();

        $cache->set("available_routes", Utils::json_encodeUTF8($routes['data']), 0);

    }

}