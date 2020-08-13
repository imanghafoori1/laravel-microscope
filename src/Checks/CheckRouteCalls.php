<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Imanghafoori\LaravelMicroscope\Analyzers\FunctionCall;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckRouteCalls
{
    public static $checkedRouteCallsNum = 0;

    public static $skippedRouteCallsNum = 0;

    public static function check($tokens, $absFilePath)
    {
        // we skip the very first tokens: '<?php '
        $i = 4;
        // we skip the very end of the file.
        $total = \count($tokens) - 3;
        while ($i < $total) {
            $index = FunctionCall::isGlobalCall('route', $tokens, $i);
            $index = $index ?: self::checkForRedirectRoute($tokens, $i);

            if (! $index) {
                $i++;
                continue;
            }

            $params = FunctionCall::readParameters($tokens, $i);

            $param1 = null;
            // it should be a hard-coded string which is not concatinated like this: 'hi'. $there
            $paramTokens = $params[0] ?? ['_', '_'];
            FunctionCall::isSolidString($paramTokens) && ($param1 = $params[0]);

            if ($param1) {
                self::$checkedRouteCallsNum++;
                self::checkRouteExists($tokens[$index][2], $param1[0][1], $absFilePath);
            } else {
                self::$skippedRouteCallsNum++;
            }
            $i++;
        }

        return $tokens;
    }

    public static function printError($value, $absPath, $lineNumber)
    {
        $p = app(ErrorPrinter::class);
        $p->route(null, "route name $value does not exist: ", "route($value)  <=== is wrong", $absPath, $lineNumber);
    }

    public static function checkRouteExists($line, $routeName, $absPath)
    {
        $matchedRoute = app('router')->getRoutes()->getByName(\trim($routeName, '\'\"'));
        is_null($matchedRoute) && self::printError($routeName, $absPath, $line);
    }

    private static function redirectRouteTokens()
    {
        return [
            '(',
            [T_STRING, 'route'],
            [T_OBJECT_OPERATOR, '->'],
            ')',
            '(',
            [T_STRING, 'redirect'],
        ];
    }

    private static function checkForRedirectRoute($tokens, $i)
    {
        $index1 = FunctionCall::checkTokens(self::redirectRouteTokens(), $tokens, $i);
        $index1 = $index1 ?: FunctionCall::isStaticCall('route', $tokens, $i, 'Redirect');
        $index1 = $index1 ?: FunctionCall::isStaticCall('route', $tokens, $i, 'URL');

        return array_pop($index1);
    }
}
