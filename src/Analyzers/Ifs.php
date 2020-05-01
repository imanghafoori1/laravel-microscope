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
        [, $condition1CloseIndex] = FunctionCall::readBody($refTokens, $condition1StartIndex, [')']);
        [, $if1BlockStartIndex] = FunctionCall::forwardTo($refTokens, $condition1CloseIndex, ['{', ':']);

        $if2index = $if1BlockStartIndex;
        while (in_array($refTokens[++$if2index][0], [T_WHITESPACE, T_COMMENT, ';'])) {}

        if ($refTokens[$if2index][0] !== T_IF) {
            return [$refTokens, $i0];
        }

        [, $condition2StartIndex] = FunctionCall::forwardTo($refTokens, $if2index, ['(']);
        [, $condition2CloseIndex] = FunctionCall::readBody($refTokens, $condition2StartIndex, [')']);
        [, $if2BodyStartIndex] = FunctionCall::forwardTo($refTokens, $condition2CloseIndex, ['{', ':']);
        [, $if2BodyCloseIndex] = FunctionCall::readBody($refTokens, $if2BodyStartIndex, ['}', T_ENDIF]);
        [, $if1BlockCloseIndex] = FunctionCall::readBody($refTokens, $if1BlockStartIndex, ['}', T_ENDIF]);
        $if1closeIndexCandid = $if2BodyCloseIndex;
        while (in_array($refTokens[++$if1closeIndexCandid][0], [T_WHITESPACE, T_COMMENT, ';'])) {}

        if ($if1closeIndexCandid !== $if1BlockCloseIndex) {
            return [$refTokens, $i0];
        }

        $afterFirstIf = FunctionCall::getNextToken($refTokens, $if1BlockCloseIndex);

        if (T_ELSEIF == $afterFirstIf[0][0] || T_ELSE == $afterFirstIf[0][0]) {
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

    static function else_If($refTokens, $i0)
    {
        $token = $refTokens[$i0];
        if ($token[0] !== T_IF) {
            return [$refTokens, $i0];
        }


        [, $condition1StartIndex] = FunctionCall::forwardTo($refTokens, $i0, ['(']);
        [$condition, $condition1CloseIndex] = FunctionCall::readBody($refTokens, $condition1StartIndex, [')']);
        [, $ifBlockStartIndex] = FunctionCall::forwardTo($refTokens, $condition1CloseIndex, ['{', ':']);
        [$ifBody, $ifBlockCloseIndex] = FunctionCall::readBody($refTokens, $ifBlockStartIndex, ['}', T_ENDIF, T_ELSEIF, T_ELSE]);

        $afterFirstIf = FunctionCall::getNextToken($refTokens, $ifBlockCloseIndex);

        if (T_ELSE !== $afterFirstIf[0][0] && $refTokens[$ifBlockCloseIndex][0] !== T_ELSE) {
            return [$refTokens, $i0];
        }

        [, $elseBodyStartIndex] = FunctionCall::forwardTo($refTokens, $ifBlockCloseIndex, ['{', ':']);
        [$elseBody, $elseBodyEndIndex] = FunctionCall::readBody($refTokens, $elseBodyStartIndex, ['}', T_ENDIF]);

        $ifIsBlocky = Refactor::isBlocky($ifBody);
        $elseIsBlocky = Refactor::isBlocky($elseBody);

        if (((count($elseBody) + 10) < count($ifBody) || count($elseBody) < count($ifBody) * 0.7) && $elseIsBlocky) {
            $refactoredTokens = [];
            foreach($refTokens as $i => $oldToken) {
                // negate the condition
                if ($condition1StartIndex == $i) {
                    $refactoredTokens[] = '(';
                    $negatedConditionTokens = Refactor::negate($condition);
                    foreach ($negatedConditionTokens as $t) {
                        $refactoredTokens[] = $t;
                    }
                    continue;
                }

                if ($i >= $condition1StartIndex && $i < $condition1CloseIndex) {
                    continue;
                }

                if ($i == $ifBlockStartIndex) {
                    $refactoredTokens[] = '{';
                    foreach ($elseBody as $t) {
                        $refactoredTokens[] = $t;
                    }
                    continue;
                }

                if ($i > $ifBlockStartIndex && $i < $ifBlockCloseIndex) {
                    continue;
                }

                // removes:   } else {
                if ($i >= $ifBlockCloseIndex && $i < $elseBodyStartIndex) {
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

            return [$refactoredTokens, 0];
        } elseif ($ifIsBlocky) {
            $refactoredTokens = [];
            foreach($refTokens as $i => $oldToken) {
                if ($i > $ifBlockCloseIndex && $i <= $elseBodyStartIndex) {
                    continue;
                }

                if ($i == $elseBodyEndIndex) {
                    continue;
                }
                $refactoredTokens[] = $oldToken;
            }
            return [$refactoredTokens, 0];
        } else {
            return [$refTokens, $i0];
        }
    }
}
