<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckView\Check;

use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class CheckViewFilesExistence implements Check
{
    public static function check(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();

        $tCount = count($tokens);
        for ($i = 0; $i < $tCount; $i++) {
            if (! self::isEnvMake($tokens, $i)) {
                continue;
            }

            $viewName = trim($tokens[$i + 4][1], '\'\"');
            CheckViewStats::$checkedCallsCount++;
            if (! View::exists($viewName)) {
                self::error($tokens, $file, $i);
            }
            $i = $i + 5;
        }
    }

    private static function isEnvMake($tokens, $i)
    {
        $varName = '$__env';
        $methods = [
            'make',
            'first',
            'renderWhen',
        ];

        // checks for this syntax: $__env->make('myViewFile', ...
        return self::isMethodCall($tokens, $i, $varName, $methods)
            && ($tokens[$i + 4][0] ?? '') == T_CONSTANT_ENCAPSED_STRING
            && ($tokens[$i + 5] ?? null) == ',';
    }

    private static function error($tokens, $file, $i)
    {
        $viewName = $tokens[$i + 4][1];
        $viewName = str_replace('.', '/', trim($viewName, '\'\"'));
        CheckView::viewError($file, $tokens[$i + 4][2], $viewName);
    }

    private static function isVariable($token, string $varName)
    {
        return ($token[0] == T_VARIABLE) && ($token[1] == $varName);
    }

    private static function isMethodCall($tokens, $i, $varName, $methods)
    {
        return self::isVariable($tokens[$i], $varName) && in_array($tokens[$i + 2][1] ?? null, $methods);
    }
}
