<?php

namespace Imanghafoori\LaravelMicroscope\Refactors;

use Imanghafoori\LaravelMicroscope\Analyzers\FunctionCall;
use Imanghafoori\LaravelMicroscope\Analyzers\Ifs;

class SyntaxNormalizer
{
    public static $hasChange;

    public static function normalizeSyntax($tokens, $replace = false)
    {
        $ends = [T_ENDFOR, T_ENDIF, T_ENDFOREACH, T_ENDWHILE];
        $start = [T_FOR, T_IF, T_FOREACH, T_WHILE, T_ELSEIF];
        $i = 0;
        $refactoredTokens = [];
        $tCount = \count($tokens);
        $ifIf = [];
        $closing = $replace ? '}' : ['}', ''];

        $opening = function ($or = '') use ($replace) {
            return $replace ? '{' : ['{', $or];
        };

        self::$hasChange = false;

        while ($tCount > $i) {
            $t = $tokens[$i];
            if (\in_array($t[0], $ends)) {
                self::$hasChange = true;
                // replace the ruby-style syntax with C-style
                $refactoredTokens[] = $replace ? '}' : ['}', $t[1]];
                $replace && self::removeSemi($tokens, $i);
                $i++;
                continue;
            }

            if (\in_array($t[0], $start)) {
                // forward to end of parenthesis
                [, , $u] = Ifs::readCondition($tokens, $i);
                // read first char after the parenthesis
                [$next, $u] = FunctionCall::getNextToken($tokens, $u);
                if ($next == ':') {
                    $tokens[$u] = $opening(':');
                    // Adds a closing curly brace "}" before elseif.
                    $t[0] == T_ELSEIF && $refactoredTokens[] = $closing;
                }
            }

            if ($t[0] == T_ELSE || $t[0] == T_IF) {
                if ($t[0] == T_ELSE) {
                    [$next_T, $next_I] = FunctionCall::getNextToken($tokens, $i);
                } else {
                    [, , $u] = Ifs::readCondition($tokens, $i);
                    [$next_T, $next_I] = FunctionCall::getNextToken($tokens, $u);
                }

                if (\in_array($next_T[0], [T_FOR, T_FOREACH, T_WHILE])) {
                    array_splice($tokens, $next_I, 0, [$opening()]);
                    $refactoredTokens[] = $t;
                    $i++;
                    [, , $u] = Ifs::readCondition($tokens, $next_I + 1);
                    [, $u] = FunctionCall::getNextToken($tokens, $u);
                    [, $u] = FunctionCall::readBody($tokens, $u);
                    array_splice($tokens, $u, 0, [$closing]);

                    // we update the count since the number of elements is changed.
                    $tCount = \count($tokens);
                    continue;
                } elseif ($next_T[0] !== T_IF && $next_T !== '{' && $next_T !== ':') {
                    /**
                     * in case if or else block is like this:
                     * if ($v) {
                     *    ...
                     * } else
                     *   $var = 0;.
                     */
                    $refactoredTokens[] = $t;
                    array_splice($tokens, $next_I - 1, 0, [$opening()]);
                    [, $endIndex] = FunctionCall::forwardTo($tokens, $i, [';']);
                    $NEXT = FunctionCall::getNextToken($tokens, $endIndex);
                    if ($NEXT[0][0] == T_ELSE && $t[0] == T_ELSE) {
                        $ia = array_pop($ifIf);
                        array_splice($refactoredTokens, $ia, 0, [$opening()]);
                        array_splice($tokens, $endIndex + 2, 0, [$closing]);
                    }
                    array_splice($tokens, $endIndex + 2, 0, [$closing]);
                    $tCount = \count($tokens);
                    $i++;
                    continue;
                } elseif ($t[0] == T_IF && $next_T[0] === T_IF) {
                    $ifIf[] = $next_I;
                }
            }

            [$next, $u] = FunctionCall::getNextToken($tokens, $i);

            if ($next == ':' && $t[0] == T_ELSE) {
                $tokens[$u] = $opening(':');
                $refactoredTokens[] = $closing;
            }

            $refactoredTokens[] = $t;
            $i++;
        }

        return $refactoredTokens;
    }

    private static function removeSemi(&$tokens, $i)
    {
        [$next, $u] = FunctionCall::getNextToken($tokens, $i);
        // replaces ";" token with a neutral token.
        $next == ';' && $tokens[$u] = ['', ''];
    }
}
