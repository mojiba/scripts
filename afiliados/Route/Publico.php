<?php

namespace Afiliados\Route;

use XF\Mvc\RouteBuilderInterface;
use XF\Http\Request;

class Publico implements \XF\Mvc\Router\RouteInterface
{
    public function match($routePath, Request $request, &$routeMatch)
    {
        $parts = explode('/', $routePath);
        $action = isset($parts[0]) ? $parts[0] : 'index';
        
        $routeMatch = [
            'controller' => 'Afiliados:Main',
            'action' => $action
        ];
        
        return true;
    }

    public function buildLink($prefix, $route, RouteBuilderInterface $builder)
    {
        return $builder->buildPublicLink('afiliados', $route);
    }
}