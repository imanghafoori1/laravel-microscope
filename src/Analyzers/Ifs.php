<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Imanghafoori\LaravelMicroscope\Refactors\IfElse;
use Imanghafoori\LaravelMicroscope\Refactors\NestedIf;

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
        if ($char[0] == T_IF) {
            [$char,] = FunctionCall::getNextToken($tokens, $if1BlockStartIndex);
            $condition2 = self::readCondition($tokens, $if1BlockStartIndex);

            $if2Body = self::readBody($tokens, $condition2[2]);

            $afterSecondIf = FunctionCall::getNextToken($tokens, $if2Body[2]);

            if (T_ELSEIF !== $afterSecondIf[0][0] && T_ELSE !== $afterSecondIf[0][0]) {
                return NestedIf::merge($tokens, $condition1[2], $condition2[0], -1);
            }
        }

        // if with no curly brace.
        if ($char[0] !== '{') {
            return null;
        }

        $if2index = self::forwardTo($tokens, $if1BlockStartIndex);

        if ($tokens[$if2index][0] !== T_IF) {
            return null;
        }

        $condition2 = self::readCondition($tokens, $if2index);

        $if2Body = self::readBody($tokens, $condition2[2]);
        [, $if1BodyCloseIndex] = FunctionCall::readBody($tokens, $if1BlockStartIndex);
        $if1closeIndexCandid = self::forwardTo($tokens, $if2Body[2]);

        if ($if1closeIndexCandid !== $if1BodyCloseIndex) {
            return null;
        }

        $afterFirstIf = FunctionCall::getNextToken($tokens, $if1BodyCloseIndex);

        if (T_ELSEIF == $afterFirstIf[0][0] || T_ELSE == $afterFirstIf[0][0]) {
            return null;
        }

        return NestedIf::merge($tokens, $condition1[2], $condition2[0], $if2Body[2]);
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

        return IfElse::refactorElseIf($tokens, $ifBody, $elseBody, $condition);
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

    private static function forwardTo($tokens, $index)
    {
        while (\in_array($tokens[++$index][0], [T_WHITESPACE, T_COMMENT, ';'])) {
        }

        return $index;
    }
}
