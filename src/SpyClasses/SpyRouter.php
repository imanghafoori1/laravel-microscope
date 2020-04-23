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
        !($routes instanceof Closure) && $this->routePaths[] = $routes;

        parent::loadRoutes($routes);
    }
}
