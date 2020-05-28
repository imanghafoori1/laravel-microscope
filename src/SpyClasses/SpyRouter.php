<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Routing\Router;

class SpyRouter extends Router
{
    public $routePaths = [];

    /**
     * @var \Imanghafoori\LaravelMicroscope\SpyClasses\SpyRouteCollection
     */
    private $routesSpy = null;

    public function spyRouteConflict()
    {
        $this->routesSpy = $this->routes = new SpyRouteCollection();
    }

    protected function loadRoutes($routes)
    {
        // This is needed to collect the route paths to tokenize and run inspections.
        ! ($routes instanceof Closure) && $this->routePaths[] = $routes;

        parent::loadRoutes($routes);
    }

    /**
     * Add a route to the underlying route collection.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string|callable|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function addRoute($methods, $uri, $action)
    {
        $i = 2;
        $excludes = [
            base_path('vendor'.DIRECTORY_SEPARATOR.'laravel'),
        ];

        while (
            ($info = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $i + 1)[$i])
            &&
            Str::startsWith(($info['file'] ?? ''), $excludes)
        ) {
            $i++;
        }
        $routeObj = $this->createRoute($methods, $uri, $action);
        $this->routesSpy && $this->routesSpy->addCallSiteInfo($routeObj, $info);

        return $this->routes->add($routeObj);
    }
}
