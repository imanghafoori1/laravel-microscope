<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use Illuminate\Routing\RouteCollection;
use Imanghafoori\LaravelMicroscope\ErrorTypes\RouteDefinitionConflict;

class SpyRouteCollection extends RouteCollection
{
    public $routesInfo;

    public function addCallSiteInfo($route, $info)
    {
        $domainAndUri = $route->getDomain().$route->uri();
        foreach ($route->methods() as $method) {
            $this->routesInfo[$method][$domainAndUri][] = $info;
        }
    }

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
                if (! $this->isItSelf($this->routesInfo[$method][$domainAndUri])) {
                    event(new RouteDefinitionConflict($this->routes[$method][$domainAndUri], $route, $this->routesInfo[$method][$domainAndUri]));
                }
            }
        }
        parent::addToCollections($route);
    }

    private function isItSelf($info)
    {
        return $info[0] == $info[1];
    }
}
