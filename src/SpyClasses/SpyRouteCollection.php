<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use Illuminate\Routing\RouteCollection;
use Imanghafoori\LaravelMicroscope\ErrorTypes\RouteDefinitionConflict;

class SpyRouteCollection extends RouteCollection
{
    /**
     * Add the given route to the arrays of routes.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    protected function addToCollections($route)
    {
        $domainAndUri = $route->getDomain().$route->uri();
        foreach ($route->methods() as $method) {
            if (isset($this->routes[$method][$domainAndUri])) {
                event(new RouteDefinitionConflict($this->routes[$method][$domainAndUri], $route));
            }
        }
        parent::addToCollections($route);
    }
}
