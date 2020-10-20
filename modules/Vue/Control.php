<?php

namespace DBModules\Vue;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\System\Cache;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;
use DBSnoop\System\Utils;

class Control extends ServerRequestControl
{
    /**
     * @Route("/vue/is_available_routes")
     * @Auth(true)
     * @Request("POST")
     * @Type("JSON")
     * @Needed({
     *  "route"
     * })
     */
    public function availableRoute(): Response\JSON
    {

        try {

            $cache = new Cache;
            $route_id = $this->REQUEST['route'];
            $session = $this->SESSION;

            if (!is_numeric($route_id)) {
                throw new \Exception("INVALID_ROUTE");
            }

            $routes = Utils::json_decodeUTF8($cache->get("available_routes"));

            $route = array_filter($routes, function ($route) use ($route_id) {
                if ($route['route_id'] == $route_id) {
                    return $route;
                }

            });

            if (empty($route)) {
                throw new \Exception("INVALID_ROUTE");
            }

            list('plan_id' => $plan, 'user_type' => $user, 'only_admin' => $admin) = array_shift($route);
            if (
                (!is_array($plan) || array_search($session['plan_id'], $plan)) &&
                (!is_array($user) || array_search($session['user_level'], $user)) &&
                ($admin == ' ' || $admin == $session['is_admin'])
            ) {

                return new Response\JSON("ok", array("valid" => true));
            } else {
                return new Response\JSON("ok", array("valid" => false));
            }
        } catch (\Exception $e) {
            return new Response\JSON("error", $e->getMessage());
        }

    }

    /**
     * @Route("/vue/get_routes")
     * @Auth(false)
     * @Request("GET")
     * @Type("JSON")
     */
    public function getRoutes(): Response\JSON
    {
        $cache = new Cache;
        $routes = Utils::json_decodeUTF8($cache->get("available_routes"));
        $childrens = array();

        $new_routes = array_filter($routes, function ($route) use (&$childrens) {
            list('children' => $child) = $route;

            if (is_numeric($child)) {
                if (!@is_array($childrens[$route['children']])) {
                    $childrens[$route['children']] = array();
                }

                array_push($childrens[$route['children']], json_decode($route['configuration'], true));
            } else {
                return $route;
            }
        });

        $new_routes = array_map(function ($route) use ($childrens) {
            $config = json_decode($route['configuration'], true);
            if (array_key_exists($route['route_id'], $childrens)) {
                $config['children'] = $childrens[$route['route_id']];
            }
            return $config;

        }, $new_routes);

        return new Response\JSON("ok", $new_routes);

    }

    /**
     * @Route("/vue/get_routes_user")
     * @Auth(true)
     * @Request("GET")
     * @Type("JSON")
     */
    public function routesUser(): Response\JSON
    {

        try {

            $cache = new Cache;
            $session = $this->SESSION;
            $childrens = array();
            $routes = Utils::json_decodeUTF8($cache->get("available_routes"));

            $new_routes = array_filter($routes, function ($route) use ($session, &$childrens) {
                list('plan_id' => $plan, 'user_type' => $user, 'only_admin' => $admin, 'children' => $child) = $route;
                $plan = $plan != ' ' ? explode(',', $plan) : false;
                $user = $user != ' ' ? explode(',', $user) : false;
                if (
                    (!is_array($plan) || array_search($session['plan_id'], $plan)) &&
                    (!is_array($user) || array_search($session['user_level'], $user)) &&
                    ($admin == ' ' || $admin == $session['is_admin'])
                ) {
                    if (is_numeric($child)) {
                        if (!is_array($childrens[$route['children']])) {
                            $childrens[$route['children']] = array();
                        }

                        array_push($childrens[$route['children']], json_decode($route['configuration'], true));
                    } else {
                        return $route;
                    }
                }
            });

            $new_routes = array_map(function ($route) use ($childrens) {
                $config = json_decode($route['configuration'], true);
                if (array_key_exists($route['route_id'], $childrens)) {
                    $config['children'] = $childrens[$route['route_id']];
                }
                return $config;

            }, $new_routes);

            return new Response\JSON("ok", $new_routes);

        } catch (\Exception $e) {
            return new Response\JSON("error", $e->getMessage());
        }
    }


    /**
     * @Route("/lang/get_lang")
     * @Auth(false)
     * @Request("GET")
     * @Needed({
     *  "lang"
     * })
     */
    public function lang(): Response\JSON
    {
        $langs = json_decode(file_get_contents(root_dir . "langs/" . $this->REQUEST['lang'] . ".json"), true);

        return new Response\JSON("ok", $langs);

    }

    /**
     * @Route("/lang/available_langs")
     * @Auth(false)
     * @Request("GET")
     */
    public function availableLangs(): Response\JSON
    {

        $files = scandir(root_dir . "langs/");
        $langs = array();
        foreach ($files as $file) {
            if ($file == "." || $file == "..") {
                continue;
            }

            preg_match("/(\w*).json/", $file, $out);
            if (isset($out[1])) {
                array_push($langs, $out[1]);
            }
        }

        return new Response\JSON("ok", $langs);

    }

}
