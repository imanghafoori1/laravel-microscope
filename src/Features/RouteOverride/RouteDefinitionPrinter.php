<?php

namespace Imanghafoori\LaravelMicroscope\Features\RouteOverride;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\PendingError;

class RouteDefinitionPrinter
{
    public static function routeDefinitionConflict($route1, $route2, $info)
    {
        if (ErrorPrinter::isIgnored($info[0]['file'] ?? 'unknown')) {
            return;
        }

        $printer = ErrorPrinter::singleton();

        $msg = self::getMsg($route1, $route2, $printer, $info);
        $methods = self::getMethods($route1);
        $key = 'routeDefinitionConflict';

        $printer->errorsList[$key][$methods] = (new PendingError($key))
            ->header('Route with uri: '.$printer->color($methods.': /'.$route1->uri()).' is overridden.')
            ->errorData($msg);
    }

    private static function getMsg($route1, $route2, ErrorPrinter $printer, $info): string
    {
        $routeName = $route1->getName();
        if ($routeName) {
            $routeName = $printer->color($routeName);
            $msg = 'Route name: '.$routeName;
        } else {
            $routeUri = $route1->uri();
            $routeUri = $printer->color($routeUri);
            $msg = 'Route uri: '.$routeUri;
        }

        $msg .= "\n".' at '.($info[0]['file'] ?? 'unknown').':'.($info[0]['line'] ?? 2);
        $msg .= "\n".' is overridden by ';

        $routeName = $route2->getName();
        if ($routeName) {
            $routeName = $printer->color($routeName);
            $msg .= 'route name: '.$routeName;
        } else {
            $msg .= 'an other route with same uri.';
        }

        $msg .= "\n".' at '.($info[1]['file'] ?? ' ').':'.$info[1]['line']."\n";

        return $msg;
    }

    private static function getMethods($route1): string
    {
        return \implode(',', $route1->methods());
    }
}
