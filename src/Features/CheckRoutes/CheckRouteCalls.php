<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckRoutes;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Cache;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\FunctionCall;

class CheckRouteCalls implements Check
{
    public static $checkedRouteCallsNum = 0;

    public static $skippedRouteCallsNum = 0;

    public static function check(PhpFileDescriptor $file)
    {
        // we skip the very first tokens: '<?php '
        [$skippedRouteCallsNum, $calls] = Cache::getForever($file->getMd5(), function () use ($file) {
            $calls = [];
            $i = 4;
            // we skip the very end of the file.
            $tokens = $file->getTokens();

            $total = count($tokens) - 3;
            $skippedRouteCallsNum = 0;
            while ($i < $total) {
                // make the check case-insensitive:
                if ($tokens[$i][0] === T_STRING && strtolower($tokens[$i][1]) === 'route') {
                    $tokens[$i][1] = 'route';
                }
                $index = FunctionCall::isGlobalCall('route', $tokens, $i);
                $index = $index ?: self::checkForRedirectRoute($tokens, $i);

                if (! $index) {
                    $i++;
                    continue;
                }

                $params = FunctionCall::readParameters($tokens, $i);

                $param1 = null;
                // it should be a hard-coded string which is not concatenated like this: 'hi'. $there
                $paramTokens = $params[0] ?? ['_', '_'];
                FunctionCall::isSolidString($paramTokens) && ($param1 = $params[0]);

                if ($param1) {
                    $calls[] = [$tokens[$index][2], $param1[0][1]];
                } else {
                    $skippedRouteCallsNum++;
                }
                $i++;
            }

            return [$skippedRouteCallsNum, $calls];
        });

        foreach ($calls as [$line, $routeName]) {
            self::checkRouteExists($routeName, $file, $line);
        }
        self::$checkedRouteCallsNum += count($calls);
        self::$skippedRouteCallsNum += $skippedRouteCallsNum;
    }

    public static function printError($routeName, $file, $lineNumber)
    {
        $routeName = Color::blue($routeName);
        self::route(
            "route($routeName)",
            'Route name does not exist: ',
            '  <=== is wrong',
            $file,
            $lineNumber
        );
    }

    public static function route($path, $errorIt, $errorTxt, $file, $lineNumber)
    {
        $p = ErrorPrinter::singleton();
        $p->simplePendError($path, $file, $lineNumber, 'route', $errorIt, $errorTxt);
    }

    public static function checkRouteExists($routeName, $file, $line)
    {
        $matchedRoute = app('router')->getRoutes()->getByName(
            trim($routeName, '\'\"')
        );
        is_null($matchedRoute) && self::printError($routeName, $file, $line);
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
