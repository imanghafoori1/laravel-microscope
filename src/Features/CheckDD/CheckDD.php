<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDD;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\FunctionCall;

class CheckDD implements Check
{
    use CachedCheck;

    public static $onErrorCallback = CheckDDHandler::class;

    private static $cacheKey = 'check_dd_command';

    public static function performCheck(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();
        $hasError = false;
        foreach ($tokens as $i => $token) {
            $name = strtolower($token[1] ?? '');
            if ($name === 'dump' || $name === 'dd') {
                $tokens[$i][1] = $name;

                continue;
            }

            if (($index = FunctionCall::isGlobalCall('dd', $tokens, $i)) || ($index = FunctionCall::isGlobalCall('dump', $tokens, $i)) || ($index = FunctionCall::isGlobalCall('ddd', $tokens, $i))) {
                (self::$onErrorCallback)::handle($file, $tokens[$index][1], $tokens[$index][2]);
                $hasError = true;
            }
        }

        return $hasError;
    }
}
