<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckView\Check;

use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\FunctionCall;

class CheckView implements Check
{
    use CachedCheck;

    /**
     * @var string
     */
    private static $cacheKey = 'check_view_command';

    public static function performCheck(PhpFileDescriptor $file)
    {
        $staticCalls = [
            'View' => ['make', 0],
            'Route' => ['view', 1],
        ];

        return self::checkViewCalls($file, $staticCalls);
    }

    private static function checkViewParams($file, &$tokens, $i, $index)
    {
        $params = FunctionCall::readParameters($tokens, $i);

        // it should be a hard-coded string which is not concatinated like this: 'hi'. $there
        $paramTokens = $params[$index] ?? ['_', '_', '_'];

        if (FunctionCall::isSolidString($paramTokens)) {
            CheckViewStats::$checkedCallsCount++;
            $viewName = self::getViewName($paramTokens[0][1]);
            if ($viewName && ! View::exists($viewName)) {
                self::viewError($file, $paramTokens[0][2], $viewName);
            }
        } else {
            CheckViewStats::$skippedCallsCount++;
        }
    }

    private static function checkViewCalls(PhpFileDescriptor $file, array $staticCalls)
    {
        $tokens = $file->getTokens();
        $hasViewCalls = false;
        foreach ($tokens as $i => $token) {
            if (FunctionCall::isGlobalCall('view', $tokens, $i)) {
                $hasViewCalls = true;
                self::checkViewParams($file, $tokens, $i, 0);
                continue;
            }

            foreach ($staticCalls as $class => $method) {
                if (FunctionCall::isStaticCall($method[0], $tokens, $i, $class)) {
                    $hasViewCalls = true;
                    self::checkViewParams($file, $tokens, $i, $method[1]);
                }
            }
        }

        return $hasViewCalls;
    }

    public static function viewError($file, $lineNumber, $fileName)
    {
        ErrorPrinter::singleton()->simplePendError(
            $fileName.'.blade.php',
            $file,
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
