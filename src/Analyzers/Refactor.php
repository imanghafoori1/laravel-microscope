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

    public static function saveTokens($file, array $refactoredTokens, $test = false)
    {
        $test && ($file = $file.'_flat');
        file_put_contents($file, self::toString($refactoredTokens));
    }

    public static function isBlocky(array $codeBlock)
    {
        $isBlocky = false;
        $depth = 0;

        foreach ($codeBlock as $token) {
            $token[0] == '}' && $depth++;
            $token[0] == '{' && $depth--;
            if ($depth == 0 && (in_array($token[0], self::blocksKeyWords))) {
                $isBlocky = true;
            }
        }

        return $isBlocky;
    }

    public static function toString($refactoredTokens)
    {
        $stringOutput = '';
        foreach ($refactoredTokens as $refToken) {
            $stringOutput .= $refToken[1] ?? $refToken[0];
        }

        return $stringOutput;
    }

    public static function negate($conditionTokens)
    {
        $found = false;

        $ops = [
            '==',
            '===',
            '>',
            '<',
            '>=',
            '<=',
            '!=',
            '!==',
        ];

        $logic = [ '&&', '||', 'or', 'and', '?:', '??', '-', '+', '*', '**', '%'];

        $ops = array_merge($ops, $logic);

        $comparison = [
            '!=' => '==',
            '!==' => '===',
            '<=' => '>',
            '>=' => '<',
            '<' => '>=',
            '>' => '<=',
            '==' => '!=',
            '===' => '!==',
        ];
        if (self::count($conditionTokens, $comparison) == 1 &&
            self::count($conditionTokens, $logic) == 0) {
            $conditionTokens = self::replace($conditionTokens, $comparison);
        } else {
            foreach ($conditionTokens as $t) {
                if (in_array($t[1] ?? $t[0], $ops)) {
                    $found = true;
                    break;
                }
            }

            if (! $found && $conditionTokens[0] != '!') {
                array_unshift($conditionTokens, '!');
            } elseif (! $found && $conditionTokens[0] == '!') {
                array_shift($conditionTokens);
            } else {
                array_unshift($conditionTokens, '(');
                array_unshift($conditionTokens, '!');
                $conditionTokens[] = ')';
            }
        }

        return $conditionTokens;
    }

    private static function count($conditionTokens, $ops)
    {
        $level = $found = 0;
        foreach ($conditionTokens as $t) {
            $t == '(' && $level++;
            $t == ')' && $level--;
            if ($level === 0 && in_array($t[1] ?? $t[0], $ops)) {
                $found++;
            }
        }

        return $found;
    }

    private static function replace($conditionTokens, $ops)
    {
        $newTokens = [];
        foreach ($conditionTokens as $t) {
            $o = $t[1] ?? $t[0];
            if (isset($ops[$o])) {
                $r = $t;
                $r[1] = $ops[$o];
                $newTokens[] = $r;
            } else {
                $newTokens[] = $t;
            }
        }

        return $newTokens;
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
