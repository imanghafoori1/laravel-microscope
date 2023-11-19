<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Imanghafoori\TokenAnalyzer\Refactor;
use Imanghafoori\TokenAnalyzer\Str;

class GenericDocblocks
{
    const statements = [
        '* Display a listing of the resource.',
        '* Show the form for creating a new resource.',
        '* Store a newly created resource in storage.',
        '* Display the specified resource.',
        '* Show the form for editing the specified resource.',
        '* Update the specified resource in storage.',
        '* Remove the specified resource from storage.',
        '* Handle the incoming request.',
    ];

    public static $command;

    public static $controllers = [];

    public static function check($tokens, $absFilePath, $params, $classFilePath, $psr4Path, $psr4Namespace)
    {
        $fullNamespace = RoutelessActions::getFullNamespace($classFilePath, $psr4Path, $psr4Namespace);

        if (! RoutelessActions::isLaravelController($fullNamespace)) {
            return null;
        }

        $hasReplacement = false;
        foreach ($tokens as $i => $token) {
            if ($token[0] !== T_DOC_COMMENT) {
                continue;
            }

            $contain = Str::contains($token[1], self::statements);

            if ($contain) {
                $hasReplacement = true;
                $tokens = self::removeDocblock($tokens, $i);
            }
        }

        if ($hasReplacement && self::getConfirm($absFilePath)) {
            Refactor::saveTokens($absFilePath, $tokens);
        }
    }

    private static function surroundedByWhitespace($tokens, $i): bool
    {
        return $tokens[$i - 1][0] === T_WHITESPACE && ($tokens[$i + 1][0] ?? 0) === T_WHITESPACE;
    }

    private static function getQuestion($absFilePath): string
    {
        return 'Do you want to remove docblocks from: <fg=yellow>'.basename($absFilePath).'</>';
    }

    private static function getConfirm($absFilePath)
    {
        return self::$command->confirm(self::getQuestion($absFilePath), true);
    }

    private static function removeDocblock($tokens, $i)
    {
        unset($tokens[$i]);
        if (self::surroundedByWhitespace($tokens, $i)) {
            unset($tokens[$i + 1]);
        }

        return $tokens;
    }
}
