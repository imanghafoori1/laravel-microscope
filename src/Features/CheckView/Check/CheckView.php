<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckView\Check;

use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\TokenAnalyzer\FunctionCall;

class CheckView implements Check
{
    public static function check(PhpFileDescriptor $file)
    {
        if (CachedFiles::isCheckedBefore('check_view_command', $file)) {
            return;
        }

        $staticCalls = [
            'View' => ['make', 0],
            'Route' => ['view', 1],
        ];

        $hasCalls = self::checkViewCalls($file, $staticCalls);

        if ($hasCalls === false) {
            CachedFiles::put('check_view_command', $file);
        }
    }

    private static function checkViewParams($absPath, &$tokens, $i, $index)
    {
        $params = FunctionCall::readParameters($tokens, $i);

        // it should be a hard-coded string which is not concatinated like this: 'hi'. $there
        $paramTokens = $params[$index] ?? ['_', '_', '_'];

        if (FunctionCall::isSolidString($paramTokens)) {
            CheckViewStats::$checkedCallsCount++;
            $viewName = self::getViewName($paramTokens[0][1]);
            if ($viewName && ! View::exists($viewName)) {
                CheckView::viewError($absPath, $paramTokens[0][2], $viewName);
            }
        } else {
            CheckViewStats::$skippedCallsCount++;
        }
    }

    public static function checkViewCalls(PhpFileDescriptor $file, array $staticCalls)
    {
        $absPath = $file->getAbsolutePath();
        $tokens = $file->getTokens();
        $hasViewCalls = false;
        foreach ($tokens as $i => $token) {
            if (FunctionCall::isGlobalCall('view', $tokens, $i)) {
                $hasViewCalls = true;
                self::checkViewParams($absPath, $tokens, $i, 0);
                continue;
            }

            foreach ($staticCalls as $class => $method) {
                if (FunctionCall::isStaticCall($method[0], $tokens, $i, $class)) {
                    $hasViewCalls = true;
                    self::checkViewParams($absPath, $tokens, $i, $method[1]);
                }
            }
        }

        return $hasViewCalls;
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
