<?php
namespace Swoole\Router;

use Swoole\IFace\Router;
use Swoole\Tool;

class MVC implements Router
{
    function handle(&$uri)
    {
        $request = \Swoole::$php->request;
        $array = \Swoole::$default_controller;
        $request_uri = explode('/', $uri, 3);
        $pathCount = count($request_uri);
        if ($pathCount < 2 || $pathCount > 3)
        {
            return $array;
        }
        $array['controller'] = '';
        if($pathCount == 3)
        {
            $array['controller'] = ucfirst($request_uri[0]);
            $request_uri_arr = explode('-', $request_uri[1]);
            $request_view_arr = explode('-', $request_uri[2]);
        } else {
            $request_uri_arr = explode('-', $request_uri[0]);
            $request_view_arr = explode('-', $request_uri[1]);
        }
        foreach ($request_uri_arr as $uri)
        {
            $array['controller'] .= ucfirst($uri);;
        }
        $array['controller'] .= 'Controller';

        $array['view'] = 'action';
        foreach($request_view_arr as $key => $view)
        {
            $array['view'] .= ucfirst($view);
        }
        return $array;
    }
}
