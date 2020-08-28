<?php

namespace Imanghafoori\LaravelMicroscope\Refactors;

use Imanghafoori\LaravelMicroscope\Analyzers\FunctionCall;
use Imanghafoori\LaravelMicroscope\Analyzers\Refactor;

class EarlyReturns
{
    const scopeKeywords = [
        T_FOREACH => 'continue',
        T_FUNCTION => 'return',
        T_WHILE => 'continue',
        T_FOR => 'continue',
    ];

    public static function apply($tokens, $changes)
    {
        $i = 0;
        while (true) {
            $token = $tokens[$i++] ?? null;

            if (! $token) {
                break;
            }

            if (! \in_array($token[0], [T_FOREACH, T_FUNCTION, T_WHILE, T_FOR])) {
                continue;
            }

            // fast-forward to the start of function body
            [$firstChar, $methodBodyStartIndex] = FunctionCall::forwardTo($tokens, $i, ['{', ';']);

            // in order to avoid checking abstract methods (with no body) and do/while
            if ($firstChar === ';' && $token[0] !== T_FOR) {
                continue;
            }

            try {
                // fast-forward to the end of function body
                [, $methodBodyCloseIndex] = FunctionCall::readBody($tokens, $methodBodyStartIndex);

                // get the very last token of function (or foreach) body.
                [$ifBody, $condition] = FunctionCall::readBackUntil($tokens, $methodBodyCloseIndex);
            } catch (\Exception $e) {
                continue;
            }

            if (! $ifBody) {
                continue;
            }

            // in order to avoid touching the else without curly braces.
            if (\in_array(FunctionCall::getNextToken($tokens, $ifBody[1][1])[0][0], [T_ELSE, T_ELSEIF])) {
                continue;
            }

            $i = $methodBodyStartIndex;

            if (self::isBigger($methodBodyCloseIndex, $ifBody)) {
                continue;
            }

            if (($ifBody[1][1] - $ifBody[1][0]) < 40) {
                continue;
            }

            $line1 = $tokens[$ifBody[1][1] + 1][2] ?? null;
            $line2 = $tokens[$ifBody[1][0] + 1][2] ?? null;

            if ($line1 && $line2 && ($line1 - $line2 < 5)) {
                continue;
            }

            $tokens = self::refactorTokens($tokens, $condition, $ifBody, $methodBodyCloseIndex, self::getKeyword($token[0]));
            $changes++;
        }

        return [$tokens, $changes];
    }

    private static function getKeyword($token)
    {
        return self::scopeKeywords[$token];
    }

    private static function refactorTokens($tokens, $condition, $ifBody, $closeMethodIndex, $keyword = 'return')
    {
        $ifBodyTokens = $ifBody[0];
        $ifIsBlocky = Refactor::isBlocky($ifBodyTokens);
        $ifBodyIndexes = $ifBody[1];

        $ifOpenParenIndex = $condition[1][0];
        $ifCloseParenIndex = $condition[1][1];
        $refactoredTokens = [];
        $conditionTokens = self::collectConditions($tokens, $ifOpenParenIndex, $ifCloseParenIndex);

        foreach ($tokens as $i => $origToken) {

            // negate the condition
            if ($ifOpenParenIndex == $i) {
                $refactoredTokens[] = '(';
                $negatedConditionTokens = Condition::negate($conditionTokens);
                foreach ($negatedConditionTokens as $t) {
                    $refactoredTokens[] = $t;
                }
                $refactoredTokens[] = ')';
                continue;
            }

            if ($i > $ifOpenParenIndex && $i < $ifCloseParenIndex) {
                continue;
            }

            // insert in the if block
            if ($ifCloseParenIndex == $i) {
                $refactoredTokens[] = '{';
                $afterIfToken = [];

                // in order to ignore ; for endif syntax
                $start = $ifBodyIndexes[1] + 1;
                if ($tokens[$start] == ';') {
                    $start++;
                }

                for ($u = $start; $u < $closeMethodIndex; $u++) {
                    $refactoredTokens[] = $tokens[$u];
                    $afterIfToken[] = $tokens[$u];
                }
                if (! Refactor::isBlocky($afterIfToken)) {
                    $refactoredTokens[] = [$keyword.';'];
                }
                $refactoredTokens[] = '}';
                continue;
            }
            // removes a curly brace from the end and start
            if ($ifBodyIndexes[1] == $i || $ifBodyIndexes[0] == $i) {
                continue;
            }

            // remove the after if
            if ($ifIsBlocky && $i > $ifBodyIndexes[1] && $i < $closeMethodIndex) {
                continue;
            }
            $refactoredTokens[] = $origToken;
        }

        return $refactoredTokens;
    }

    private static function isBigger($methodBodyCloseIndex, $ifBody)
    {
        $ifBlockLength = $ifBody[1][1] - $ifBody[1][0];
        $afterIfLength = $methodBodyCloseIndex - $ifBody[1][1];

        return ($afterIfLength + 20) > $ifBlockLength;
    }

    private static function collectConditions($tokens, $ifOpenParenIndex, $ifCloseParenIndex)
    {
        // collect condition tokens
        $conditionTokens = [];
        for ($o = $ifOpenParenIndex + 1; $o < $ifCloseParenIndex; $o++) {
            $conditionTokens[] = $tokens[$o];
        }

        return $conditionTokens;
    }
}
