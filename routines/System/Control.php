<?php

namespace DBRoutines\System;

use DBSnoop\Annotations\{Active, Interval, StartRunning};
use DBSnoop\SysControl\VueControl;
use DBSnoop\System\Cache;
use DBSnoop\System\Utils;

class Control{

    /**
     * 
     * @Interval(60)
     * @StartRunning
     */
    public function loadRoutes(){
        \DBSnoop\System\DataBase::recreateDAO();
    }

}