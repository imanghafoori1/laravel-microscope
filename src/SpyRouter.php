<?php

namespace Imanghafoori\LaravelMicroscope;

use Closure;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteFileRegistrar;

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
