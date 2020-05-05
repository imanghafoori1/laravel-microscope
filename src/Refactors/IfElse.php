<?php

namespace Imanghafoori\LaravelMicroscope\Refactors;

use Imanghafoori\LaravelMicroscope\Analyzers\Refactor;

class IfElse
{
    public static function refactorElseIf($tokens, $ifBody, $elseBody, $condition)
    {
        if (Refactor::isBlocky($elseBody[1]) && self::shouldBeFlipped(count($elseBody[1]), count($ifBody[1]))) {
            return self::flipElseIf($tokens, $condition, $ifBody, $elseBody);
        } elseif (Refactor::isBlocky($ifBody[1])) {
            return self::removeTokens($tokens, $ifBody[2], $elseBody[0], $elseBody[2]);
        } else {
            return null;
        }
    }

    private static function shouldBeFlipped($elseCount, $ifBody)
    {
        $ifIsLonger = ($elseCount + 10) < $ifBody;

        return ($ifIsLonger || ($elseCount < $ifBody * 0.7));
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

    private static function flipElseIf($tokens, $condition, $ifBody, $elseBody) {

        [$ifBlockStartIndex, $ifBody, $ifBlockEndIndex] = $ifBody;
        [$elseBodyStartIndex, $elseBody, $elseBodyEndIndex] = $elseBody;
        [$conditionStartIndex, $condition, $conditionCloseIndex] = $condition;

        $refactoredTokens = [];
        foreach ($tokens as $i => $oldToken) {
            // negate the condition
            if ($conditionStartIndex == $i) {
                $refactoredTokens[] = '(';
                $negatedConditionTokens = Condition::negate($condition);
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

            // close the body and what was in the else block after it.
            if ($i == $elseBodyStartIndex) {
                $refactoredTokens[] = '}';
                foreach ($ifBody as $t) {
                    $refactoredTokens[] = $t;
                }
            }

            // ignore the else body.
            if ($i >= $elseBodyStartIndex && $i <= $elseBodyEndIndex) {
                continue;
            }

            $refactoredTokens[] = $oldToken;
        }

        return $refactoredTokens;
    }
}
