<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Routing\Router;
use Illuminate\Contracts\Events\Dispatcher;

class SpyRouter extends Router
{
    public $routePaths = [];

    public function __construct(Dispatcher $events, Container $container = null)
    {
        parent::__construct($events, $container);
        $this->routes = new SpyRouteCollection();
    }

    protected function loadRoutes($routes)
    {
        !($routes instanceof Closure) && $this->routePaths[] = $routes;

        parent::loadRoutes($routes);
    }
}
