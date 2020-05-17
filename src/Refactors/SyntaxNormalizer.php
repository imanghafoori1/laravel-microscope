<?php

namespace Imanghafoori\LaravelMicroscope\Refactors;

use Imanghafoori\LaravelMicroscope\Analyzers\Ifs;
use Imanghafoori\LaravelMicroscope\Analyzers\FunctionCall;

class SyntaxNormalizer
{
    public static function normalizeSyntax($tokens)
    {
        $ends = [T_ENDFOR, T_ENDIF, T_ENDFOREACH, T_ENDWHILE,];
        $start = [T_FOR, T_IF, T_FOREACH, T_WHILE, T_ELSEIF];
        $i = 0;
        $refactoredTokens = [];
        $tCount = count($tokens);

        while ($tCount > $i) {
            $t = $tokens[$i];
            if (in_array($t[0], $ends)) {
                $refactoredTokens[] = ['}', $t[1]];
                $i++;
                continue;
            }
            if (in_array($t[0], $start)) {
                [, , $u] = Ifs::readCondition($tokens, $i);
                [$next, $u] = FunctionCall::getNextToken($tokens, $u);
                if ($next == ':') {
                    $tokens[$u] = ['{', ':'];
                    // Adds a closing curly brace "}" before elseif.
                    $t[0] == T_ELSEIF && $refactoredTokens[] = ['}', ''];
                }
            }

            [$next, $u] = FunctionCall::getNextToken($tokens, $i);

            if ($next == ':' && $t[0] == T_ELSE) {
                $tokens[$u] = ['{', ':'];
                $refactoredTokens[] = ['}', ''];
            }

            $refactoredTokens[] = $t;
            $i++;
        }

        return $refactoredTokens;
    }
}
