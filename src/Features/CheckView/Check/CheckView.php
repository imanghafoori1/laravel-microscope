<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckView\Check;

use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\Check;
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

        Loop::mapIf(
            $views,
            fn ($view) => ! View::exists($view[1]),
            fn ($view) => CheckViewHandler::handle($file, $view[0], $view[1])
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
                [$tokens, $skippedViews, $views] = self::process($tokens, $i, 0, $skippedViews, $views);

                continue;
            }

            foreach ($staticCalls as $class => $method) {
                if (! FunctionCall::isStaticCall($method[0], $tokens, $i, $class)) {
                    continue;
                }
                [$tokens, $skippedViews, $views] = self::process($tokens, $i, $method[1], $skippedViews, $views);
            }
        }

        return [$views, $skippedViews];
    }

    private static function getViewName($string)
    {
        $viewName = trim($string, '\'\"');

        return str_replace('.', '/', $viewName);
    }

    private static function getFromCache($md5, PhpFileDescriptor $file)
    {
        $key = 'check_views_call';
        if (isset(Cache::$cache[$key][$md5])) {
            return Cache::$cache[$key][$md5];
        }

        [$views, $skipped] = self::checkViewCalls($file, [
            'View' => ['make', 0],
            'Route' => ['view', 1],
        ]);

        if ($views || $skipped) {
            Cache::$cache[$key][$md5] = [$views, $skipped];
        }

        return [$views, $skipped];
    }

    private static function process($tokens, $i, $index, $skippedViews, $views): array
    {
        [$line, $view] = self::checkViewParams($tokens, $i, $index);

        if ($view === null) {
            $skippedViews[] = $line;
        } else {
            $views[] = [$line, $view];
        }

        return [$tokens, $skippedViews, $views];
    }
}
