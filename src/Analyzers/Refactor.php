<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class Refactor
{
    const blocksKeyWords = [T_RETURN, T_THROW, T_CONTINUE, T_BREAK];

    const scopeKeywords = [
        T_FOREACH => 'continue',
        T_FUNCTION => 'return',
        T_WHILE => 'continue',
        T_FOR => 'continue',
    ];

    static function flatten($tokens)
    {
        $refactoredTokens = self::normalizeSyntax($tokens);

        $i = 0;
        $changes = 0;
        do {
            $result = Ifs::mergeIfs($refactoredTokens, $i);
            $i++;
            if ($result) {
                $refactoredTokens = $result;
                $i = 1; // rewind
                $changes++; // count changes
            }
        } while (isset($refactoredTokens[$i]));

        $i = 1;
        do {
            $result = Ifs::else_If($refactoredTokens, $i);
            $i++;
            if ($result) {
                $refactoredTokens = $result;
                $i = 1; // rewind
                $changes++; // count changes
            }
        } while (isset($refactoredTokens[$i]));

        $i = 0;
        while (true) {
            $token = $refactoredTokens[$i++] ?? null;

            if (! $token) {
                break;
            }

            if (! in_array($token[0], [T_FOREACH, T_FUNCTION, T_WHILE, T_FOR])) {
                continue;
            }

            // fast-forward to the start of function body
            [$firstChar, $methodBodyStartIndex] = FunctionCall::forwardTo($refactoredTokens, $i, ['{', ';']);

            // in order to avoid checking abstract methods (with no body) and do/while
            if ($firstChar === ';' && $token[0] !== T_FOR) {
                continue;
            }

            try {
                // fast-forward to the end of function body
                [, $methodBodyCloseIndex] = FunctionCall::readBody($refactoredTokens, $methodBodyStartIndex);

                // get the very last token of function (or foreach) body.
                [$ifBody, $condition] = FunctionCall::readBackUntil($refactoredTokens, $methodBodyCloseIndex);
            } catch (\Exception $e) {
                // this is a work around for tokenizer weird bugs,
                // which makes it impossible to process the syntax.
                // so, we just ignore the current scope and continue.
                continue;
            }

            if (! $ifBody) {
                continue;
            }

            if (in_array(FunctionCall::getNextToken($refactoredTokens, $ifBody[1][1])[0][0], [T_ELSE, T_ELSEIF])) {
                continue;
            }

            $i = $methodBodyStartIndex;

            if (self::isBigger($methodBodyCloseIndex, $ifBody)) {
                continue;
            }

            $refactoredTokens = self::refactorTokens(
                $refactoredTokens,
                $condition,
                $ifBody,
                $methodBodyCloseIndex,
                self::getKeyword($token[0])
            );
            $changes++;
        }

        return [$refactoredTokens, $changes];
    }

    static function saveTokens($file, array $refactoredTokens, $test = false)
    {
        $test && ($file = $file.'_flat');
        file_put_contents($file, self::toString($refactoredTokens));
    }

    private static function refactorTokens($tokens, $condition, $ifBody, $closeMethodIndex, $keyword = 'return')
    {
        $ifBodyTokens = $ifBody[0];
        $ifIsBlocky = self::isBlocky($ifBodyTokens);
        $ifBodyIndexes = $ifBody[1];

        $ifOpenParenIndex = $condition[1][0];
        $ifCloseParenIndex = $condition[1][1];
        $refactoredTokens = [];
        $conditionTokens = self::collectConditions($tokens, $ifOpenParenIndex, $ifCloseParenIndex);

        foreach ($tokens as $i => $origToken) {

            // negate the condition
            if ($ifOpenParenIndex == $i) {
                $refactoredTokens[] = '(';
                $negatedConditionTokens = self::negate($conditionTokens);
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
                if (! self::isBlocky($afterIfToken)) {
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

    /**
     * @param  array  $orphanBlock
     *
     * @return bool
     */
    static function isBlocky(array $orphanBlock)
    {
        $orphanIsBlocky = false;
        $depth = 0;

        foreach ($orphanBlock as $orphanToken) {
            $orphanToken[0] == '}' && $depth++;
            $orphanToken[0] == '{' && $depth--;
            if ($depth == 0 && (in_array($orphanToken[0], self::blocksKeyWords))) {
                $orphanIsBlocky = true;
            }
        }

        return $orphanIsBlocky;
    }

    private static function getKeyword($token)
    {
        return self::scopeKeywords[$token];
    }

    static function toString($refactoredTokens)
    {
        $stringOutput = '';
        foreach ($refactoredTokens as $refToken) {
            $stringOutput .= $refToken[1] ?? $refToken[0];
        }

        return $stringOutput;
    }

    static function negate($conditionTokens)
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

    private static function isBigger($methodBodyCloseIndex, $ifBody)
    {
        $ifBlockLength = $ifBody[1][1] - $ifBody[1][0];
        $afterIfLength = $methodBodyCloseIndex - $ifBody[1][1];

        return (($afterIfLength + 25) > $ifBlockLength);
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

    private static function collectConditions($tokens, $ifOpenParenIndex, $ifCloseParenIndex)
    {
        // collect condition tokens
        $conditionTokens = [];
        for ($o = $ifOpenParenIndex + 1; $o < $ifCloseParenIndex; $o++) {
            $conditionTokens[] = $tokens[$o];
        }

        return $conditionTokens;
    }

    private static function normalizeSyntax($tokens)
    {
        $ends = [T_ENDFOR, T_ENDIF, T_ENDFOREACH, T_ENDWHILE,];
        $start = [T_FOR, T_IF, T_FOREACH, T_WHILE, T_ELSEIF];
        $i = 0;
        $refactoredTokens = [];

        while (count($tokens) > $i) {
            $t = $tokens[$i];
            if (in_array($t[0], $ends)) {
                $refactoredTokens[] = ['}', $t[1]];
                $i++;
                continue;
            }
            if (in_array($t[0], $start)) {
                [, , $u] = Ifs::readCondition($tokens, $i);
                [$next, $u] = FunctionCall::getNextToken($tokens, $u);
                $next == ':' && $tokens[$u] = ['{', ':'];
            }
            if ($t[0] == T_ELSEIF) {
                $refactoredTokens[] = '}';
            }
            $refactoredTokens[] = $t;
            $i++;
        }

        return $refactoredTokens;
    }
}
