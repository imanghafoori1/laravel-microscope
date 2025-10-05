<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDD;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\FunctionCall;

class CheckDD implements Check
{
    use CachedCheck;

    public static $onErrorCallback;

    private static $cacheKey = 'check_dd_command';

    public static function performCheck(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();
        $callback = self::$onErrorCallback;
        $hasError = false;
        foreach ($tokens as $i => $token) {
            if (($index = FunctionCall::isGlobalCall('dd', $tokens, $i)) || ($index = FunctionCall::isGlobalCall('dump', $tokens, $i)) || ($index = FunctionCall::isGlobalCall('ddd', $tokens, $i))) {
                $callback($file, $tokens[$index]);
                $hasError = true;
            }
        }

        return $hasError;
    }
}
