<?php

namespace Imanghafoori\LaravelMicroscope\Refactors;

class Condition
{
    public static function negate($conditionTokens)
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

        $logic = ['&&', '||', 'or', 'and', '?:', '??', '-', '+', '*', '**', '%', '<=>'];

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
            return self::replace($conditionTokens, $comparison);
        }

        foreach ($conditionTokens as $t) {
            if (\in_array($t[1] ?? $t[0], $ops)) {
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

        return $conditionTokens;
    }

    private static function count($conditionTokens, $ops)
    {
        $level = $found = 0;
        foreach ($conditionTokens as $t) {
            $t == '(' && $level++;
            $t == ')' && $level--;
            if ($level === 0 && \in_array($t[1] ?? $t[0], $ops)) {
                $found++;
            }
        }

        return $found;
    }

    private static function replace($conditionTokens, $ops)
    {
        $newTokens = [];
        foreach ($conditionTokens as $token) {
            $char = \is_array($token) ? $token[1] : $token[0];
            if (isset($ops[$char])) {
                $r = (array) $token;
                $r[1] = $ops[$char];

                $newTokens[] = $r;
            } else {
                $newTokens[] = $token;
            }
        }

        return $newTokens;
    }
}
