<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class Ifs
{
    static function mergeIfs($tokens, $i)
    {
        $token = $tokens[$i];
        if ($token[0] !== T_IF) {
            return null;
        }
        [, $condition1StartIndex] = FunctionCall::forwardTo($tokens, $i, ['(']);
        [, $condition1CloseIndex] = FunctionCall::readBody($tokens, $condition1StartIndex, [')']);
        [$char, $if1BlockStartIndex] = FunctionCall::getNextToken($tokens, $condition1CloseIndex);
        // if with no curly brace.
        if (! in_array($char, ['{', ':'])) {
            return null;
        }

        $if2index = $if1BlockStartIndex;
        while (in_array($tokens[++$if2index][0], [T_WHITESPACE, T_COMMENT, ';'])) {}

        if ($tokens[$if2index][0] !== T_IF) {
            return null;
        }

        [, $condition2StartIndex] = FunctionCall::forwardTo($tokens, $if2index, ['(']);
        [, $condition2CloseIndex] = FunctionCall::readBody($tokens, $condition2StartIndex, [')']);
        [$char, $if2BodyStartIndex] = FunctionCall::getNextToken($tokens, $condition2CloseIndex);
        // if with no curly brace.
        if (! in_array($char, ['{', ':'])) {
            return null;
        }

        [, $if2BodyCloseIndex] = FunctionCall::readBody($tokens, $if2BodyStartIndex, ['}', T_ENDIF]);
        [, $if1BlockCloseIndex] = FunctionCall::readBody($tokens, $if1BlockStartIndex, ['}', T_ENDIF]);
        $if1closeIndexCandid = $if2BodyCloseIndex;
        while (in_array($tokens[++$if1closeIndexCandid][0], [T_WHITESPACE, T_COMMENT, ';'])) {}

        if ($if1closeIndexCandid !== $if1BlockCloseIndex) {
            return null;
        }

        $afterFirstIf = FunctionCall::getNextToken($tokens, $if1BlockCloseIndex);

        if (T_ELSEIF == $afterFirstIf[0][0] || T_ELSE == $afterFirstIf[0][0]) {
            return null;
        }

        return self::refactorMergeIf($tokens, $condition1CloseIndex, $condition2StartIndex, $if2BodyCloseIndex);
    }

    static function else_If($tokens, $i0)
    {
        $token = $tokens[$i0];
        if ($token[0] !== T_IF) {
            return null;
        }

        [, $condition1StartIndex] = FunctionCall::forwardTo($tokens, $i0, ['(']);
        [$condition, $condition1CloseIndex] = FunctionCall::readBody($tokens, $condition1StartIndex, [')']);
        [$char, $ifBlockStartIndex] = FunctionCall::getNextToken($tokens, $condition1CloseIndex);

        // if with no curly brace.
        if (! in_array($char, ['{', ':'])) {
            return null;
        }

        [$ifBody, $ifBlockCloseIndex] = FunctionCall::readBody($tokens, $ifBlockStartIndex, ['}', T_ENDIF,]);

        $afterFirstIf = FunctionCall::getNextToken($tokens, $ifBlockCloseIndex);

        if (T_ELSE !== $afterFirstIf[0][0] && $tokens[$ifBlockCloseIndex][0] !== T_ELSE) {
            return null;
        }

        [$char, $elseBodyStartIndex] = FunctionCall::getNextToken($tokens, $afterFirstIf[1]);
        // if with no curly brace.
        if (! in_array($char, ['{', ':'])) {
            return null;
        }

        [$elseBody, $elseBodyEndIndex] = FunctionCall::readBody($tokens, $elseBodyStartIndex, ['}', T_ENDIF]);

        $ifIsBlocky = Refactor::isBlocky($ifBody);
        $elseIsBlocky = Refactor::isBlocky($elseBody);

        if (((count($elseBody) + 10) < count($ifBody) || (count($elseBody) < count($ifBody) * 0.7) && $elseIsBlocky)) {
            $refactoredTokens = [];
            foreach($tokens as $i => $oldToken) {
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

            return $refactoredTokens;
        } elseif ($ifIsBlocky) {
            $refactoredTokens = [];
            foreach($tokens as $i => $oldToken) {
                if ($i > $ifBlockCloseIndex && $i <= $elseBodyStartIndex) {
                    continue;
                }

                if ($i == $elseBodyEndIndex) {
                    continue;
                }
                $refactoredTokens[] = $oldToken;
            }
            return $refactoredTokens;
        } else {
            return null;
        }
    }

    private static function refactorMergeIf($tokens, $condition1CloseIndex, $condition2StartIndex, $if2BodyCloseIndex)
    {
        $newTokens = [];
        foreach ($tokens as $i => $oldToken) {
            if ($i == $condition1CloseIndex) {
                $newTokens[] = [T_WHITESPACE, ' '];
                $newTokens[] = [T_BOOLEAN_AND, '&&'];
                $newTokens[] = [T_WHITESPACE, ' '];
                continue;
            }

            if ($i > $condition1CloseIndex && $i <= $condition2StartIndex) {
                continue;
            }

            if ($i == $if2BodyCloseIndex || ($i == $if2BodyCloseIndex + 1 && $oldToken == ';')) {
                continue;
            }
            $newTokens[] = $oldToken;
        }

        return $newTokens;
    }
}
