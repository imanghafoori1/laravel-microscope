<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckRoutes;

use Illuminate\Routing\RouteCollection;
use Imanghafoori\LaravelMicroscope\Features\RouteOverride\RouteDefinitionPrinter;

class SpyRouteCollection extends RouteCollection
{
    public $routesInfo;

    public function addCallSiteInfo($route, $info)
    {
        $domainAndUri = $this->_getDomainAndUrl($route);
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
        $domainAndUri = $this->_getDomainAndUrl($route);
        foreach ($route->methods() as $method) {
            if (isset($this->routes[$method][$domainAndUri])) {
                if (! $this->isItSelf($this->routesInfo[$method][$domainAndUri])) {
                    $this->reportDefinitionConflict($method, $domainAndUri, $route);
                }
            }
        }
        parent::addToCollections($route);
    }

    private function isItSelf($info)
    {
        return $info[0] == $info[1];
    }

    private function _getDomainAndUrl($route)
    {
        if (version_compare(app()->version(), '5.5.0', '<')) {
            $getDomain = 'domain';
        } else {
            $getDomain = 'getDomain';
        }

        return $route->$getDomain().$route->uri();
    }

    private function reportDefinitionConflict($method, string $domainAndUri, $route)
    {
        RouteDefinitionPrinter::routeDefinitionConflict(
            $this->routes[$method][$domainAndUri],
            $route,
            $this->routesInfo[$method][$domainAndUri]
        );
    }
}
