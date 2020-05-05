<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class Ifs
{
    public static function mergeIfs($tokens, $i)
    {
        $token = $tokens[$i];
        if ($token[0] !== T_IF) {
            return null;
        }
        $condition1 = self::readCondition($tokens, $i);
        [$char, $if1BlockStartIndex] = FunctionCall::getNextToken($tokens, $condition1[2]);
        // if with no curly brace.
        if ($char[0] !== '{') {
            return null;
        }

        $if2index = $if1BlockStartIndex;
        while (in_array($tokens[++$if2index][0], [T_WHITESPACE, T_COMMENT, ';'])) {}

        if ($tokens[$if2index][0] !== T_IF) {
            return null;
        }

        $condition2 = self::readCondition($tokens, $i);

        $if2Body = self::readBody($tokens, $condition2[2]);
        [, $if1BodyCloseIndex] = FunctionCall::readBody($tokens, $if1BlockStartIndex);
        $if1closeIndexCandid = $if2Body[2];
        while (in_array($tokens[++$if1closeIndexCandid][0], [T_WHITESPACE, T_COMMENT, ';'])) {}

        if ($if1closeIndexCandid !== $if1BodyCloseIndex) {
            return null;
        }

        $afterFirstIf = FunctionCall::getNextToken($tokens, $if1BodyCloseIndex);

        if (T_ELSEIF == $afterFirstIf[0][0] || T_ELSE == $afterFirstIf[0][0]) {
            return null;
        }

        return self::refactorMergeIf($tokens, $condition1[2], $condition2[0], $if2Body[2]);
    }

    public static function else_If($tokens, $i)
    {
        $token = $tokens[$i];
        if ($token[0] !== T_IF) {
            return null;
        }

        $condition = self::readCondition($tokens, $i);

        $ifBody = self::readBody($tokens, $condition[2]);
        if (! $ifBody[0]) {
            return null;
        }

        [$afterIf, $afterIfIndex] = FunctionCall::getNextToken($tokens, $ifBody[2]);

        // in order to cover both   } else {   and   else:   syntax.
        if (T_ELSE !== $afterIf[0] && $tokens[$ifBody[2]][0] !== T_ELSE) {
            return null;
        }

        $elseBody = self::readBody($tokens, $afterIfIndex);

        if (! $elseBody[0]) {
            return null;
        }

        return self::refactorElseIf($tokens, $ifBody, $elseBody, $condition);
    }

    private static function refactorMergeIf($tokens, $cond1EndIndex, $cond2StartIndex, $if2BodyEndIndex)
    {
        $newTokens = [];
        foreach ($tokens as $i => $oldToken) {
            if ($i == $cond1EndIndex) {
                $newTokens[] = [T_WHITESPACE, ' '];
                $newTokens[] = [T_BOOLEAN_AND, '&&'];
                $newTokens[] = [T_WHITESPACE, ' '];
                continue;
            }

            if ($i > $cond1EndIndex && $i <= $cond2StartIndex) {
                continue;
            }

            if ($i == $if2BodyEndIndex || ($i == $if2BodyEndIndex + 1 && $oldToken == ';')) {
                continue;
            }
            $newTokens[] = $oldToken;
        }

        return $newTokens;
    }

    private static function flipElseIf($tokens, $condition, $ifBody, $elseBody) {

        [$ifBlockStartIndex, $ifBody, $ifBlockEndIndex] = $ifBody;
        [$elseBodyStartIndex, $elseBody, $elseBodyEndIndex] = $elseBody;
        [$conditionStartIndex, $condition, $conditionCloseIndex] = $condition;

        $refactoredTokens = [];
        foreach ($tokens as $i => $oldToken) {
            // negate the condition
            if ($conditionStartIndex == $i) {
                $refactoredTokens[] = '(';
                $negatedConditionTokens = Refactor::negate($condition);
                foreach ($negatedConditionTokens as $t) {
                    $refactoredTokens[] = $t;
                }
                continue;
            }

            if ($i >= $conditionStartIndex && $i < $conditionCloseIndex) {
                continue;
            }

            if ($i == $ifBlockStartIndex) {
                $refactoredTokens[] = '{';
                foreach ($elseBody as $t) {
                    $refactoredTokens[] = $t;
                }
                continue;
            }

            if ($i > $ifBlockStartIndex && $i < $ifBlockEndIndex) {
                continue;
            }

            // removes:   } else {
            if ($i >= $ifBlockEndIndex && $i < $elseBodyStartIndex) {
                continue;
            }

            if ($i == $elseBodyStartIndex) {
                $refactoredTokens[] = '}';
                foreach ($ifBody as $t) {
                    $refactoredTokens[] = $t;
                }
            }

            if ($i >= $elseBodyStartIndex && $i <= $elseBodyEndIndex) {
                continue;
            }

            $refactoredTokens[] = $oldToken;
        }

        return $refactoredTokens;
    }

    private static function removeTokens($tokens, $from, $to, $at)
    {
        $refactoredTokens = [];
        foreach ($tokens as $i => $oldToken) {
            if ($i > $from && $i <= $to) {
                continue;
            }

            if ($i == $at) {
                continue;
            }
            $refactoredTokens[] = $oldToken;
        }

        return $refactoredTokens;
    }

    private static function shouldBeFlipped($elseCount, $ifBody)
    {
        $ifIsLonger = ($elseCount + 10) < $ifBody;

        return ($ifIsLonger || ($elseCount < $ifBody * 0.7));
    }

    public static function readCondition($tokens, $i)
    {
        [, $conditionStartIndex] = FunctionCall::forwardTo($tokens, $i, ['(']);
        [$condition, $conditionCloseIndex] = FunctionCall::readBody($tokens, $conditionStartIndex, ')');

        return [$conditionStartIndex, $condition, $conditionCloseIndex];
    }

    private static function readBody($tokens, $afterIfIndex)
    {
        [$char, $elseBodyStartIndex] = FunctionCall::getNextToken($tokens, $afterIfIndex);
        // if with no curly brace.
        if ($char[0] !== '{') {
            return [null, null, null];
        }

        [$elseBody, $elseBodyEndIndex] = FunctionCall::readBody($tokens, $elseBodyStartIndex);

        return [$elseBodyStartIndex, $elseBody, $elseBodyEndIndex];
    }

    private static function refactorElseIf($tokens, $ifBody, $elseBody, $condition)
    {
        $ifIsBlocky = Refactor::isBlocky($ifBody[1]);
        $elseIsBlocky = Refactor::isBlocky($elseBody[1]);

        if ($elseIsBlocky && self::shouldBeFlipped(count($elseBody[1]), count($ifBody[1]))) {
            return self::flipElseIf($tokens, $condition, $ifBody, $elseBody);
        } elseif ($ifIsBlocky) {
            return self::removeTokens($tokens, $ifBody[2], $elseBody[0], $elseBody[2]);
        } else {
            return null;
        }
    }
}
