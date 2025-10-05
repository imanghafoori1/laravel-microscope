<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDD;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\TokenAnalyzer\FunctionCall;

class CheckDD implements Check
{
    public static $onErrorCallback;

    public static function check(PhpFileDescriptor $file)
    {
        if (CachedFiles::isCheckedBefore('check_dd_command', $file)) {
            return;
        }
        $tokens = $file->getTokens();
        $callback = self::$onErrorCallback;
        $hasError = false;
        foreach ($tokens as $i => $token) {
            if (($index = FunctionCall::isGlobalCall('dd', $tokens, $i)) || ($index = FunctionCall::isGlobalCall('dump', $tokens, $i)) || ($index = FunctionCall::isGlobalCall('ddd', $tokens, $i))) {
                $callback($file, $tokens[$index]);
                $hasError = true;
            }
        }

        if ($hasError === false) {
            CachedFiles::put('check_dd_command', $file);
        }
    }
}
