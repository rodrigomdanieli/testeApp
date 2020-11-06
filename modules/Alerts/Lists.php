<?php

namespace DBModules\Alerts;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;
use DBSnoop\Lists\Alert as ListsAlert;

class Lists extends ServerRequestControl
{
    
    /**
     *
     * @Route("/alert/list_by_schema")
     * @Auth(true)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "schema"
     * })
     */
    public function list_by_schema(): Response\JSON
    {
        $field_schema = $this->REQUEST['schema'];

        if(empty($field_schema)){
            return new Response\JSON("error", "EMPTY_FIELD");
        }

        try {
            $filter_schema = array(
                "schema"  => $field_schema,
            );
            $list = new ListsAlert($filter_schema);
            
            
            return new Response\JSON("ok", $list->toArray());
        } catch (\Throwable $th) {
            return new Response\JSON("error", $th->getMessage());
        }

    }

    
}
