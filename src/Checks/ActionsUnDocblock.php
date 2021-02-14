<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\Refactor;

class ActionsUnDocblock
{
    public static $command;

    public static function check($tokens, $absFilePath, $classFilePath, $psr4Path, $psr4Namespace)
    {
        $fullNamespace = RoutelessActions::getFullNamespace($classFilePath, $psr4Path, $psr4Namespace);

        if (RoutelessActions::isLaravelController($fullNamespace)) {
            self::removeGenericDocBlocks($tokens, $classFilePath->getRealpath());
        }
    }

    private static function removeGenericDocBlocks($tokens, $absFilePath)
    {
        foreach ($tokens as $i => $token) {
            if ($token[0] == T_DOC_COMMENT) {
                if (Str::contains($token[1], [
                    'Remove the specified resource from storage.',
                    'Update the specified resource in storage.',
                    'Store a newly created resource in storage.',
                    'Display the specified resource.',
                    'Display a listing of the resource.',
                    'Show the form for creating a new resource.',
                    'Show the form for editing the specified resource.',
                ])) {
                    unset($tokens[$i]);
                    if ($tokens[$i + 1][0] == T_WHITESPACE) {
                        unset($tokens[$i + 1]);
                    }
                    Refactor::saveTokens($absFilePath, $tokens);
                }
            }

            if (\in_array($token[0], [T_PUBLIC, T_PRIVATE, T_PROTECTED]) && ($tokens[$i - 1][0] == T_WHITESPACE)) {
                if (($tokens[$i - 2][0] == '}') && $tokens[$i - 1][1] != "\n\n    ") {
                    $tokens[$i - 1][1] = "\n\n    ";
                    Refactor::saveTokens($absFilePath, $tokens);
                }

                if (($tokens[$i - 2][0] == '{') && $tokens[$i - 1][1] != "\n    ") {
                    $tokens[$i - 1][1] = "\n    ";
                    Refactor::saveTokens($absFilePath, $tokens);
                }
            }
        }
    }
}
