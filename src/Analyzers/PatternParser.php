<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Illuminate\Support\Str;

class PatternParser
{
    public static function parsePatterns($refactorPatterns)
    {
        $tokens_to_search_for = [];
        // $placeholders = [];
        // $counter = 0;

        foreach ($refactorPatterns as $pattern => $to) {
            $tokens = token_get_all('<?php '.$pattern);
            array_shift($tokens);

            //$station = $j = 0;
            foreach ($tokens as $i => $token) {
                // transform placeholders
                if ($placeHolder = self::isPlaceHolder($token)) {
                    $token = $tokens[$i] = [$placeHolder, null];
                }

                /*
                if (self::isWildcard($token)) {
                    $placeholders[$counter][] = self::parseWildcard($token);
                    $tokens_to_search_for[$counter] = array_merge($tokens_to_search_for[$counter], [$j => array_slice($tokens, $station, $i - $station)]);
                    $station = $i + 1;
                    $j++;
                }*/
            }
            $tokens_to_search_for[] = [$tokens];
            /*
             * $tokens_to_search_for[$counter] = array_merge($tokens_to_search_for[$counter], [$j => array_slice($tokens, $station, $i - $station + 1)]);
             * */
        }

        return $tokens_to_search_for;
    }

    public static function search($patterns, array $sampleFileTokens)
    {
        $patternsTokens = PatternParser::parsePatterns($patterns);

        return PatternParser::findMatches($patternsTokens, $sampleFileTokens);
    }

    public static function findMatches($patterns, array $fileTokens): array
    {
        $matches = [];

        foreach ($patterns as $pIndex => $pattern) {
            foreach ($pattern as $patternChunkTokens) {
                $pToken = $patternChunkTokens[0];
                $i = 0;
                $allCount = count($fileTokens);
                while ($i < $allCount) {
                    $token = $fileTokens[$i];
                    if (PatternParser::areTheSame($pToken, $token)) {
                        $isMatch = PatternParser::compareTokens($patternChunkTokens, $fileTokens, $i);
                        if ($isMatch) {
                            [$k, $matchedValues] = $isMatch;
                            $matches[$pIndex][] = [['start' => $i, 'end' => $k], $matchedValues];
                            $i = $k; // fast-forward
                        }
                    }
                    $i++;
                }
            }
        }

        return $matches;
    }

    public static function parseWildcard($token)
    {
        $t = ltrim($token[1], '\'-');
        return rtrim($t, ' token\'');
    }

    public static function isWildcard($token)
    {
        return $token[0] == T_CONSTANT_ENCAPSED_STRING && Str::endsWith($token[1], ' token-\'') && Str::startsWith($token[1], '\'-');
    }

    public static function areTheSame($pToken, $token)
    {
        if ($pToken[0] !== $token[0]) {
            return false;
        }

        if ($pToken[0] === T_CONSTANT_ENCAPSED_STRING && $pToken[1] === null) {
            return 'placeholder';
        }

        if (! isset($pToken[1]) || ! isset($token[1])) {
            return true;
        }

        return $pToken[1] === $token[1];
    }

    public static function compareTokens($pattern, $tokens, int $i)
    {
        $j = 0;
        $tCount = count($tokens);
        $pCount = count($pattern);
        $placeholderValues = [];

        $tToken = $tokens[$i];
        $pToken = $pattern[$j];

        while ($i < $tCount && $j < $pCount) {
            $same = self::areTheSame($pToken, $tToken);

            if (! $same) {
                return false;
            }

            if ($same === 'placeholder') {
                $placeholderValues[] = $tToken;
            }

            [$tToken, $i] = self::getNextToken($tokens, $i);
            [$pToken, $j] = self::getNextToken($pattern, $j);
        }

        if ($pCount === $j) {
            return [$i, $placeholderValues];
        }

        return false;
    }

    public static function getNextToken($tokens, $i)
    {
        $i++;
        $token = $tokens[$i] ?? '_';
        while ($token[0] == T_WHITESPACE || $token[0] == T_COMMENT) {
            $i++;
            $token = $tokens[$i] ?? [null, null];
        }

        return [$token, $i];
    }

    private static function isPlaceHolder($token)
    {
        if ($token[0] === T_CONSTANT_ENCAPSED_STRING && $token[1] === "'<string>'") {
            return T_CONSTANT_ENCAPSED_STRING;
            //return trim($token[1], '\'<>');
        }
    }
}
