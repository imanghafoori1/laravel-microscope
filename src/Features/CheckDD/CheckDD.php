<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDD;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\FunctionCall;

class CheckDD implements Check
{
    use CachedCheck;

    /**
     * @var string
     */
    private static $cacheKey = 'check_dd_command';

    private static function performCheck(PhpFileDescriptor $file, $params): bool
    {
        $tokens = $file->getTokens();
        $callback = $params[0];
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
