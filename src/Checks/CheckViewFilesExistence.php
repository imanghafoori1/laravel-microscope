<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckViewFilesExistence
{
    public static function check($tokens, $absPath)
    {
        $tCount = count($tokens);
        for ($i = 0; $i < $tCount; $i++) {
            if (! self::isEnvMake($tokens, $i)) {
                continue;
            }

            $viewName = trim($tokens[$i + 4][1], '\'\"');
            if (! View::exists($viewName)) {
                self::error($tokens, $absPath, $i);
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

    private static function error($tokens, $absPath, $i)
    {
        $p = app(ErrorPrinter::class);
        $p->print('included view: '.$tokens[$i + 4][1].' does not exist in blade file');
        $p->printLink($absPath, $tokens[$i + 4][2]);
        $p->end();
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
