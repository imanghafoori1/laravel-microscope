<?php

namespace Imanghafoori\LaravelMicroscope\Features\RouteOverride;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\PendingError;
use Imanghafoori\LaravelMicroscope\Foundations\Color;

class RouteDefinitionPrinter
{
    public static function routeDefinitionConflict($route1, $route2, $info)
    {
        if (ErrorPrinter::isIgnored($info[0]['file'] ?? 'unknown')) {
            return;
        }

        $printer = ErrorPrinter::singleton();

        $msg = self::getMsg($route1, $route2, $info);
        $methods = self::getMethods($route1);
        $key = 'routeDefinitionConflict';

        $uri = Color::blue("$methods: /".$route1->uri());
        $printer->errorsList[$key][$methods] = (new PendingError($key))
            ->header("Route with uri: $uri is overridden.")
            ->errorData($msg);
    }

    private static function getMsg($route1, $route2, $info): string
    {
        $routeName = $route1->getName();
        if ($routeName) {
            $routeName = Color::blue($routeName);
            $msg = "Route name: $routeName";
        } else {
            $routeUri = Color::blue($route1->uri());
            $msg = "Route uri: $routeUri";
        }

        $msg .= "\n".' at '.($info[0]['file'] ?? 'unknown').':'.($info[0]['line'] ?? 2);
        $msg .= "\n".' is overridden by ';

        $routeName = $route2->getName();
        if ($routeName) {
            $routeName = Color::blue($routeName);
            $msg .= "route name: $routeName";
        } else {
            $msg .= 'an other route with same uri.';
        }

        $msg .= "\n".' at '.($info[1]['file'] ?? ' ').':'.$info[1]['line']."\n";

        return $msg;
    }

    private static function getMethods($route1): string
    {
        return implode(',', $route1->methods());
    }
}
