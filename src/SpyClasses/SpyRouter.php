<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use Closure;
use Illuminate\Routing\RouteFileRegistrar;
use Illuminate\Routing\Router;

class SpyRouter extends Router
{
    public $routePaths = [];

    protected function loadRoutes($routes)
    {
        if ($routes instanceof Closure) {
            $routes($this);
        } else {
            $this->routePaths[] = $routes;
            (new RouteFileRegistrar($this))->register($routes);
        }
    }
}
