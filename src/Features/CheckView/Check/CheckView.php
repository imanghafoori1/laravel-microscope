<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckView\Check;

use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckView\BladeFile;
use Imanghafoori\TokenAnalyzer\FunctionCall;

class CheckView
{
    public static $checkedCallsCount = 0;

    public static $skippedCallsCount = 0;

    public static function check($tokens, $absPath)
    {
        $staticCalls = [
            'View' => ['make', 0],
            'Route' => ['view', 1],
        ];

        self::checkViewCalls($tokens, $absPath, $staticCalls);
    }

    private static function checkViewParams($absPath, &$tokens, $i, $index)
    {
        $params = FunctionCall::readParameters($tokens, $i);

        // it should be a hard-coded string which is not concatinated like this: 'hi'. $there
        $paramTokens = $params[$index] ?? ['_', '_', '_'];

        if (FunctionCall::isSolidString($paramTokens)) {
            self::$checkedCallsCount++;
            $viewName = \trim($paramTokens[0][1], '\'\"');

            $viewName = str_replace('.', '/', $viewName);
            $viewName && ! View::exists($viewName) && BladeFile::warn($absPath, $paramTokens[0][2], $viewName);
        } else {
            self::$skippedCallsCount++;
        }
    }

    public static function checkViewCalls($tokens, $absPath, array $staticCalls)
    {
        foreach ($tokens as $i => $token) {
            if (FunctionCall::isGlobalCall('view', $tokens, $i)) {
                self::checkViewParams($absPath, $tokens, $i, 0);
                continue;
            }

            foreach ($staticCalls as $class => $method) {
                if (FunctionCall::isStaticCall($method[0], $tokens, $i, $class)) {
                    self::checkViewParams($absPath, $tokens, $i, $method[1]);
                }
            }
        }

        return $tokens;
    }

    public static function viewError($absPath, $message, $lineNumber, $fileName)
    {
        ErrorPrinter::singleton()->simplePendError(
            $fileName.'.blade.php',
            $absPath,
            $lineNumber,
            'missing_view',
            \trim($message),
            ' does not exist'
        );
    }
}
