<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class Ifs
{
    static function mergeIfs($refTokens, $i0)
    {
        $token = $refTokens[$i0];
        if ($token[0] !== T_IF) {
            return [$refTokens, $i0];
        }
        [, $condition1StartIndex] = FunctionCall::forwardTo($refTokens, $i0, ['(']);
        [, $condition1CloseIndex] = FunctionCall::readBody($refTokens, $condition1StartIndex, ')');
        [, $if1BlockStartIndex] = FunctionCall::forwardTo($refTokens, $condition1CloseIndex, ['{']);

        $if2index = $if1BlockStartIndex;
        while (in_array($refTokens[++$if2index][0], [T_WHITESPACE, T_COMMENT])) {}

        if ($refTokens[$if2index][0] !== T_IF) {
            return [$refTokens, $i0];
        }

        [, $condition2StartIndex] = FunctionCall::forwardTo($refTokens, $if2index, ['(']);
        [, $condition2CloseIndex] = FunctionCall::readBody($refTokens, $condition2StartIndex, ')');
        [, $if2BodyStartIndex] = FunctionCall::forwardTo($refTokens, $condition2CloseIndex, ['{']);
        [, $if2BodyCloseIndex] = FunctionCall::readBody($refTokens, $if2BodyStartIndex);
        [, $if1BodyCloseIndex] = FunctionCall::readBody($refTokens, $if1BlockStartIndex);

        $if1closeIndexCandid = $if2BodyCloseIndex;
        while (in_array($refTokens[++$if1closeIndexCandid][0], [T_WHITESPACE, T_COMMENT])) {}

        if ($if1closeIndexCandid !== $if1BodyCloseIndex) {
            return [$refTokens, $i0];
        }

        $afterFirstIf = FunctionCall::getNextToken($refTokens, $if1BodyCloseIndex);

        if (T_ELSE == $afterFirstIf[0][0] || T_ELSEIF == $afterFirstIf[0][0]) {
            return [$refTokens, $i0];
        }

        $newTokens = [];
        foreach($refTokens as $i => $oldToken) {
            if ($i == $condition1CloseIndex) {
                $newTokens[] = [T_WHITESPACE, ' '];
                $newTokens[] = [T_BOOLEAN_AND, '&&'];
                $newTokens[] = [T_WHITESPACE, ' '];
                continue;
            }

            if ($i > $condition1CloseIndex && $i <= $condition2StartIndex) {
                continue;
            }

            if ($i == $if2BodyCloseIndex) {
                continue;
            }
            $newTokens[] = $oldToken;
        }

        return [$newTokens, 0];
    }
}
