<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use Closure;
use Illuminate\Routing\Router;

class SpyRouter extends Router
{
    public $routePaths = [];

    public function spyRouteConflict()
    {
        $this->routes = new SpyRouteCollection();
    }

    protected function loadRoutes($routes)
    {
        !($routes instanceof Closure) && $this->routePaths[] = $routes;

        parent::loadRoutes($routes);
    }
}
