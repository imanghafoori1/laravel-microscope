<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckView\Check;

use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\FunctionCall;

class CheckView implements Check
{
    public static $checkedCallsCount = 0;

    public static $skippedCallsCount = 0;

    public static function check(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();
        $absPath = $file->getAbsolutePath();

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
            $viewName = self::getViewName($paramTokens[0][1]);
            if ($viewName && ! View::exists($viewName)) {
                CheckView::viewError($absPath, $paramTokens[0][2], $viewName);
            }
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

    public static function viewError($absPath, $lineNumber, $fileName)
    {
        ErrorPrinter::singleton()->simplePendError(
            $fileName.'.blade.php',
            $absPath,
            $lineNumber,
            'missing_view',
            'The blade file is missing:',
            ' does not exist'
        );
    }

    private static function getViewName($string)
    {
        $viewName = trim($string, '\'\"');

        return str_replace('.', '/', $viewName);
    }
}
