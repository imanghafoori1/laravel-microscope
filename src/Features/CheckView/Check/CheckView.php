<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckView\Check;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Cache;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
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
        [$views, $skipped] = self::getFromCache($file->getMd5(), $file);

        Loop::over(
            $views,
            fn ($view) => self::viewError($file, $view[0], $view[1])
        );
        $skippedCount = count($skipped);
        $viewsCount = count($views);
        CheckViewStats::$skippedCallsCount += $skippedCount;
        CheckViewStats::$checkedCallsCount += $viewsCount;

        return ($skippedCount + $viewsCount) !== 0;
    }

    private static function checkViewParams(&$tokens, $i, $index)
    {
        $params = FunctionCall::readParameters($tokens, $i);

        // it should be a hard-coded string which is not concatenated like this: 'hi'. $there
        $paramTokens = $params[$index] ?? ['_', '_', '_'];
        $lineNumber = $paramTokens[0][2];

        if (FunctionCall::isSolidString($paramTokens)) {
            return [$lineNumber, self::getViewName($paramTokens[0][1])];
        } else {
            return [$lineNumber, null];
        }
    }

    private static function checkViewCalls(PhpFileDescriptor $file, array $staticCalls)
    {
        $tokens = $file->getTokens();
        $views = [];
        $skippedViews = [];
        foreach ($tokens as $i => $token) {
            if (FunctionCall::isGlobalCall('view', $tokens, $i)) {
                [$line, $view] = self::checkViewParams($tokens, $i, 0);

                if ($view === null) {
                    $skippedViews[] = $line;
                } else {
                    $views[] = [$line, $view];
                }

                continue;
            }

            foreach ($staticCalls as $class => $method) {
                if (FunctionCall::isStaticCall($method[0], $tokens, $i, $class)) {
                    [$line, $view] = self::checkViewParams($tokens, $i, $method[1]);

                    if ($view === null) {
                        $skippedViews[] = $line;
                    } else {
                        $views[] = [$line, $view];
                    }
                }
            }
        }

        return [$views, $skippedViews];
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

    private static function getFromCache($md5, PhpFileDescriptor $file)
    {
        if (isset(Cache::$cache[$md5])) {
            return Cache::$cache[$md5];
        }

        [$views, $skipped] = self::checkViewCalls($file, [
            'View' => ['make', 0],
            'Route' => ['view', 1],
        ]);

        if ($views || $skipped) {
            Cache::$cache[$md5] = [$views, $skipped];
        }

        return [$views, $skipped];
    }
}
