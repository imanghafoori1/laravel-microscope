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
            $token = GlobalFunctionCall::isGlobalFunctionCall('route', $tokens, $i);
            if (! $token) {
                $i++;
                continue;
            }

            $params = GlobalFunctionCall::readParameters($tokens, $i);

            $param1 = null;
            // it should be a hard-coded string which is not concatinated like this: 'hi'. $there
            $paramTokens = $params[0] ?? ['_', '_'];
            GlobalFunctionCall::isSolidString($paramTokens) && ($param1 = $params[0]);

            $param1 && self::checkRouteExists($token[2], $param1[0][1], $absFilePath);
            $i++;
        }

        return $tokens;
    }

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
