<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Imanghafoori\LaravelMicroscope\Analyzers\GlobalFunctionCall;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckRouteCalls
{
    public static function check($tokens, $absFilePath)
    {
        // we skip the very first tokens: '<?php '
        $i = 4;
        // we skip the very end of the file.
        $total = count($tokens) - 3;
        while ($i < $total) {
            [$param1, $matchedToken] = GlobalFunctionCall::detect('route', $tokens, $i);
            $param1 && self::checkRouteExists($matchedToken[2], $param1, $absFilePath);
            $i++;
        }

        return $tokens;
    }

    /**
     * @param $value
     * @param $absPath
     * @param $lineNumber
     */
    public static function printError($value, $absPath, $lineNumber)
    {
        $p = app(ErrorPrinter::class);
        $p->route(null, "route name $value does not exist: ", "route($value)   <====   is wrong", $absPath, $lineNumber);
    }

    public static function checkRouteExists($line, $routeName, $absPath)
    {
        $matchedRoute = app('router')->getRoutes()->getByName(trim($routeName, '\'\"'));
        is_null($matchedRoute) && self::printError($routeName, $absPath, $line);
    }
}
