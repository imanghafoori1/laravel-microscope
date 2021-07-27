<?php

namespace Imanghafoori\LaravelMicroscope\Refactor;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\Refactor;

class PatternParser
{
    public static function replaceTokens($tokens, $from, $to, string $with)
    {
        $lineNumber = 0;

        for ($i = $from; $i <= $to; $i++) {
            if ($i === $from) {
                $lineNumber = $tokens[$i][2] ?? 0;
                $tokens[$i] = [T_STRING, $with, 1];
                continue;
            }

            if ($i > $from && $i <= $to) {
                ! $lineNumber && ($lineNumber = $tokens[$i][2] ?? 0);
                $tokens[$i] = [T_STRING, '', 1];
            }
        }

        return [$tokens, $lineNumber];
    }

    private static $placeHolders = [T_CONSTANT_ENCAPSED_STRING, T_VARIABLE, T_LNUMBER, T_STRING];

    public static function parsePatterns($refactorPatterns)
    {
        $tokens_to_search_for = [];

        foreach ($refactorPatterns as $pattern => $to) {
            $tokens_to_search_for[] = ['search' => self::analyzeTokens($pattern)] + $to;
        }

        return $tokens_to_search_for;
    }

    public static function search($patterns, $sampleFileTokens)
    {
        $patternsTokens = self::parsePatterns($patterns);

        return self::findMatches($patternsTokens, $sampleFileTokens);
    }

    public static function searchReplace($patterns, $sampleFileTokens)
    {
        $matches = self::search($patterns, $sampleFileTokens);
        [$sampleFileTokens, $replacementLines] = self::applyPatterns($patterns, $matches, $sampleFileTokens);

        return [Refactor::toString($sampleFileTokens), $replacementLines];
    }

    public static function findMatches($patterns, $fileTokens)
    {
        $matches = [];

        foreach ($patterns as $pIndex => $pattern) {
            foreach ($pattern['search'] as $pToken) {
                $i = 0;
                $allCount = count($fileTokens);
                while ($i < $allCount) {
                    $token = $fileTokens[$i];
                    if (PatternParser::areTheSame($pToken, $token)) {
                        $isMatch = PatternParser::compareTokens($pattern['search'], $fileTokens, $i);
                        if ($isMatch) {
                            [$k, $matchedValues] = $isMatch;
                            $matches[$pIndex][] = [['start' => $i, 'end' => $k], 'values' => $matchedValues];
                            $i = $k - 1; // fast-forward
                        }
                    }
                    $i++;
                }
            }
        }

        return $matches;
    }

    public static function isWildcard($token)
    {
        return $token[0] == T_CONSTANT_ENCAPSED_STRING && trim($token[1], '\'\"') == '<until>';
    }

    public static function isUntilMatch($token)
    {
        return $token[0] == T_CONSTANT_ENCAPSED_STRING && trim($token[1], '\'\"') == '<until_match>';
    }

    public static function isWhiteSpace($token)
    {
        return $token[0] == T_CONSTANT_ENCAPSED_STRING && trim($token[1], '\'\"?') == '<white_space>';
    }

    public static function isComment($token)
    {
        return $token[0] == T_CONSTANT_ENCAPSED_STRING && trim($token[1], '\'\"?') == '<comment>';
    }

    public static function isOptional($token)
    {
        return Str::endsWith(trim($token, '\'\"'), '?');
    }

    public static function areTheSame($pToken, $token)
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

    public static function compareTokens($pattern, $tokens, int $i)
    {
        $pi = $j = 0;
        $tCount = count($tokens);
        $pCount = count($pattern);
        $placeholderValues = [];

        $tToken = $tokens[$i];
        $pToken = $pattern[$j];

        while ($i < $tCount && $j < $pCount) {
            if (self::isWildcard($pToken)) {
                $untilTokens = [];
                $line = 1;
                for ($k = $pi + 1; $tokens[$k] !== $pattern[$j + 1]; $k++) {
                    ! $line && isset($tokens[$k][2]) && $line = $tokens[$k][2];
                    $untilTokens[] = $tokens[$k];
                }
                $i = $k - 1;
                $placeholderValues[] = [T_STRING, Refactor::toString($untilTokens), $line];
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

                $i = $k - 1;
                $placeholderValues[] = [T_STRING, Refactor::toString($untilTokens), $line];
            } elseif (self::isWhiteSpace($pToken)) {
                if ($tToken[0] !== T_WHITESPACE) {
                    if (self::isOptional($pToken[1])) {
                        $i--;
                        $placeholderValues[] = [T_WHITESPACE, ''];
                    } else {
                        return false;
                    }
                } else {
                    $placeholderValues[] = $tToken;
                }
            } elseif (self::isComment($pToken)) {
                if ($tToken[0] !== T_COMMENT) {
                    if (self::isOptional($pToken[1])) {
                        $i--;
                        $placeholderValues[] = [T_WHITESPACE, ''];
                    } else {
                        return false;
                    }
                } else {
                    $placeholderValues[] = $tToken;
                }
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

            if (self::isWhiteSpace($pToken) || self::isComment($pToken)) {
                $pi = $i;
                $tToken = $tokens[++$i] ?? [null, null];
            } else {
                $pi = $i;
                [$tToken, $i] = self::getNextToken($tokens, $i);
            }
        }

        if ($pCount === $j) {
            return [$pi, $placeholderValues];
        }

        return false;
    }

    public static function getNextToken($tokens, $i)
    {
        $i++;
        $token = $tokens[$i] ?? '_';
        while (in_array($token[0], [T_WHITESPACE, T_COMMENT, ','], true)) {
            $i++;
            $token = $tokens[$i] ?? [null, null];
        }

        return [$token, $i];
    }

    private static function isPlaceHolder($token)
    {
        if ($token[0] !== T_CONSTANT_ENCAPSED_STRING) {
            return false;
        }
        $map = [
            "'<string>'" => T_CONSTANT_ENCAPSED_STRING,
            "'<str>'" => T_CONSTANT_ENCAPSED_STRING,
            "'<variable>'" => T_VARIABLE,
            "'<var>'" => T_VARIABLE,
            "'<number>'" => T_LNUMBER,
            "'<name>'" => T_STRING,
            "'<boolean>'" => T_STRING,
            "'<bool>'" => T_STRING,
        ];

        return $map[$token[1]] ?? false;
    }

    public static function applyPatterns($patterns, $matches, $sampleFileTokens)
    {
        $replacePatterns = array_values($patterns);

        $replacementLines = [];
        foreach ($matches as $pi => $p_match) {
            foreach ($p_match as $i => $match) {
                $newValue = $replacePatterns[$pi];
                foreach ($match['values'] as $number => $value) {
                    $newValue = str_replace('"<'.($number + 1).'>"', $value[1], $newValue);
                }
                [$sampleFileTokens, $lineNum] = self::replaceTokens($sampleFileTokens, $match[0]['start'], $match[0]['end'], $newValue);
                $replacementLines[] = $lineNum;
            }
        }

        return [$sampleFileTokens, $replacementLines];
    }

    private static function getAnti(string $startingToken)
    {
        return [
            '(' => ')',
            '{' => '}',
            '[' => ']',
        ][$startingToken];
    }

    private static function analyzeTokens($pattern)
    {
        $tokens = token_get_all('<?php '.$pattern);
        array_shift($tokens);

        foreach ($tokens as $i => $token) {
            // transform placeholders
            if ($placeHolder = self::isPlaceHolder($token)) {
                $tokens[$i] = [$placeHolder, null];
            }
        }

        return $tokens;
    }
}
