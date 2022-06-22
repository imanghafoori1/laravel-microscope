<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Imanghafoori\TokenAnalyzer\Refactor;
use Imanghafoori\TokenAnalyzer\Str;

class GenericDocblocks
{
    public static $command;

    public static $controllers = [];

    public static function check($tokens, $absFilePath, $classFilePath, $psr4Path, $psr4Namespace)
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

            $contain = Str::contains($token[1], [
                '* Display a listing of the resource.',
                '* Show the form for creating a new resource.',
                '* Store a newly created resource in storage.',
                '* Display the specified resource.',
                '* Show the form for editing the specified resource.',
                '* Update the specified resource in storage.',
                '* Remove the specified resource from storage.',
                '* Handle the incoming request.',
            ]);

            if ($contain) {
                $hasReplacement = true;
                unset($tokens[$i]);
                if ($tokens[$i - 1][0] === T_WHITESPACE && ($tokens[$i + 1][0] ?? 0) === T_WHITESPACE) {
                    unset($tokens[$i + 1]);
                }
            }
        }

        $question = 'Do you want to remove docblocks from: <fg=yellow>'.basename($absFilePath).'</>';
        if ($hasReplacement && (self::$command)->confirm($question, true)) {
            Refactor::saveTokens($absFilePath, $tokens);
        }
    }
}
