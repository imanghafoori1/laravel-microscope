<?php

namespace Imanghafoori\LaravelMicroscope\Refactor;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\Refactor;

class TokenCompare
{
    private static $placeHolders = [T_CONSTANT_ENCAPSED_STRING, T_VARIABLE, T_LNUMBER, T_STRING];

    private static function compareTokens($pattern, $tokens, $startFrom)
    {
        $pi = $j = 0;
        $tCount = count($tokens);
        $pCount = count($pattern);
        $placeholderValues = [];

        $tToken = $tokens[$startFrom];
        $pToken = $pattern[$j];

        while ($startFrom < $tCount && $j < $pCount) {
            if (self::isWildcard($pToken)) {
                $untilTokens = [];
                $line = 1;
                for ($k = $pi + 1; $tokens[$k] !== $pattern[$j + 1]; $k++) {
                    ! $line && isset($tokens[$k][2]) && $line = $tokens[$k][2];
                    $untilTokens[] = $tokens[$k];
                }
                $startFrom = $k - 1;
                $placeholderValues[] = [T_STRING, Stringify::fromTokens($untilTokens), $line];
            } elseif (self::isUntilMatch($pToken)) {
                $untilTokens = [];
                $line = 1;
                $level = 0;
                $startingToken = ($pattern[$j - 1]); // may use getPreviousToken()
                if (in_array($startingToken, ['(', '[', '{'], true)) {
                    $anti = self::getAnti($startingToken);
                } else {
                    dd('pattern invalid');
                }
                if ($anti !== $pattern[$j + 1]) {
                    dd('pattern invalid');
                }

                for ($k = $pi + 1; true; $k++) {
                    if ($tokens[$k] === $anti && $level === 0) {
                        break;
                    }

                    if ($startingToken === $tokens[$k]) {
                        $level--;
                    }
                    if ($anti === $tokens[$k]) {
                        $level++;
                    }
                    ! $line && isset($tokens[$k][2]) && $line = $tokens[$k][2];
                    $untilTokens[] = $tokens[$k];
                }

                $startFrom = $k - 1;
                $placeholderValues[] = [T_STRING, Stringify::fromTokens($untilTokens), $line];
            } elseif (self::isWhiteSpace($pToken)) {
                $placeholderValues[] = self::process($tToken, T_WHITESPACE, $pToken[1], $startFrom);
            } elseif (self::isComment($pToken)) {
                $placeholderValues[] = self::process($tToken, T_COMMENT, $pToken[1], $startFrom);
            } else {
                $same = self::areTheSame($pToken, $tToken);

                if (! $same) {
                    return false;
                }

                if ($same === 'placeholder') {
                    $placeholderValues[] = $tToken;
                }
            }

            [$pToken, $j] = self::getNextToken($pattern, $j);

            $pi = $startFrom;
            if (self::isWhiteSpace($pToken) || self::isComment($pToken)) {
                $tToken = $tokens[++$startFrom] ?? [null, null];
            } else {
                [$tToken, $startFrom] = self::getNextToken($tokens, $startFrom);
            }
        }

        if ($pCount === $j) {
            return [$pi, $placeholderValues];
        }

        return false;
    }

    private static function getNextToken($tokens, $i)
    {
        $i++;
        $token = $tokens[$i] ?? '_';
        while (in_array($token[0], [T_WHITESPACE, T_COMMENT, ','], true)) {
            $i++;
            $token = $tokens[$i] ?? [null, null];
        }

        return [$token, $i];
    }

    private static function isWildcard($token)
    {
        return $token[0] === T_CONSTANT_ENCAPSED_STRING && trim($token[1], '\'\"') === '<until>';
    }

    private static function isUntilMatch($token)
    {
        return $token[0] === T_CONSTANT_ENCAPSED_STRING && trim($token[1], '\'\"') === '<until_match>';
    }

    private static function isWhiteSpace($token)
    {
        return $token[0] === T_CONSTANT_ENCAPSED_STRING && trim($token[1], '\'\"?') === '<white_space>';
    }

    private static function isComment($token)
    {
        return $token[0] === T_CONSTANT_ENCAPSED_STRING && trim($token[1], '\'\"?') === '<comment>';
    }

    private static function isOptional($token)
    {
        return Str::endsWith(trim($token, '\'\"'), '?');
    }

    private static function getAnti(string $startingToken)
    {
        return [
            '(' => ')',
            '{' => '}',
            '[' => ']',
        ][$startingToken];
    }

    private static function areTheSame($pToken, $token)
    {
        if ($pToken[0] !== $token[0]) {
            return false;
        }

        if (in_array($pToken[0], self::$placeHolders, true) && $pToken[1] === null) {
            return 'placeholder';
        }

        if (! isset($pToken[1]) || ! isset($token[1])) {
            return true;
        }

        if ($token[0] === T_CONSTANT_ENCAPSED_STRING) {
            return trim($pToken[1], '\'\"') === trim($token[1], '\'\"');
        }

        if ($pToken[0] === T_STRING && (in_array(strtolower($pToken[1]), ['true', 'false', 'null'], true))) {
            return strtolower($pToken[1]) === strtolower($token[1]);
        }

        return $pToken[1] === $token[1];
    }

    public static function getMatch($search, $fileTokens, array $matches, $pIndex)
    {
        foreach ($search as $pToken) {
            $i = 0;
            $allCount = count($fileTokens);
            while ($i < $allCount) {
                $token = $fileTokens[$i];
                if (self::areTheSame($pToken, $token)) {
                    $isMatch = self::compareTokens($search, $fileTokens, $i);
                    if ($isMatch) {
                        [$k, $matchedValues] = $isMatch;
                        $matches[$pIndex][] = [['start' => $i, 'end' => $k], 'values' => $matchedValues];
                        $i = $k - 1; // fast-forward
                    }
                }
                $i++;
            }
        }

        return $matches;
    }

    private static function process($tToken, int $type, $token, &$i)
    {
        if ($tToken[0] !== $type) {
            if (self::isOptional($token)) {
                $i--;
                $output = [T_WHITESPACE, ''];
            } else {
                $output = null;
            }
        } else {
            $output = $tToken;
        }

        return $output;
    }
}
