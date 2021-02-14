<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Imanghafoori\LaravelMicroscope\Refactors\EarlyReturns;
use Imanghafoori\LaravelMicroscope\Refactors\SyntaxNormalizer;

class Refactor
{
    public const blocksKeyWords = [T_RETURN, T_THROW, T_CONTINUE, T_BREAK];

    public static function flatten($tokens)
    {
        $tokens = SyntaxNormalizer::normalizeSyntax($tokens);

        [$tokens, $changes1] = self::recursiveRefactor($tokens, function ($tokens, $i) {
            return Ifs::mergeIfs($tokens, $i);
        });

        [$tokens, $changes2] = self::recursiveRefactor($tokens, function ($tokens, $i) {
            return Ifs::else_If($tokens, $i);
        });

        $changes = $changes1 + $changes2;

        return EarlyReturns::apply($tokens, $changes);
    }

    public static function saveTokens($path, array $tokens, $test = false)
    {
        $test && ($path = $path.'_flat');
        file_put_contents($path, self::toString($tokens));
    }

    public static function isBlocky(array $codeBlock)
    {
        $isBlocky = false;
        $depth = 0;

        foreach ($codeBlock as $token) {
            $token[0] == '}' && $depth++;
            $token[0] == '{' && $depth--;
            if ($depth == 0 && (\in_array($token[0], self::blocksKeyWords))) {
                $isBlocky = true;
            }
        }

        return $isBlocky;
    }

    public static function toString($tokens)
    {
        $string = '';

        foreach ($tokens as $token) {
            $string .= $token[1] ?? $token[0];
        }

        return $string;
    }

    private static function recursiveRefactor($tokens, $refactor)
    {
        $i = $changes = 0;

        do {
            $result = $refactor($tokens, $i);
            $i++;
            if ($result) {
                $tokens = $result;
                $i = 0; // rewind
                $changes++;
            }
        } while (isset($tokens[$i]));

        return [$tokens, $changes];
    }
}
