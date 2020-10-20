<?php

namespace DBModules\Servers;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Extension\Graphic;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;

class Graphics extends ServerRequestControl
{

    /**
     *
     * @Route("/server/get_list_status_graphics")
     * @Auth("true")
     *
     */
    public function getListStatusGraph(): Response\JSON
    {

        
        $graph = new Graphic();
        $retorno = array_map(function ($graph){
            unset($graph['graph_query']);
            unset($graph['graph_active']);
            return $graph;
        },$graph->getAllStatusGraphic());
        return new Response\JSON("ok", $retorno);
        

    }

}
