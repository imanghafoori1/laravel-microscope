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
        // This is needed to collect the route paths to tokenize and run inspections.
        !($routes instanceof Closure) && $this->routePaths[] = $routes;

        parent::loadRoutes($routes);
    }
}
