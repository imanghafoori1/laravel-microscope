<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDD;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\FunctionCall;

class CheckDD implements Check
{
    public static function check(PhpFileDescriptor $file, $params)
    {
        $tokens = $file->getTokens();
        $absPath = $file->getAbsolutePath();

        $callback = $params[0];
        foreach ($tokens as $i => $token) {
            if (
                ($index = FunctionCall::isGlobalCall('dd', $tokens, $i)) ||
                ($index = FunctionCall::isGlobalCall('microscope_pretty_print_route', $tokens, $i)) ||
                ($index = FunctionCall::isGlobalCall('microscope_dd_listeners', $tokens, $i)) ||
                ($index = FunctionCall::isGlobalCall('microscope_write_route', $tokens, $i)) ||
                ($index = FunctionCall::isGlobalCall('dump', $tokens, $i)) ||
                ($index = FunctionCall::isGlobalCall('ddd', $tokens, $i))
            ) {
                $callback($tokens, $absPath, $tokens[$index]);
            }
        }
    }
}
