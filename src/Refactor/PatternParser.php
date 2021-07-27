<?php

namespace Imanghafoori\LaravelMicroscope\Refactor;

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
            $matches = TokenCompare::getMatch($pattern['search'], $fileTokens, $matches, $pIndex);
        }

        return $matches;
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
            foreach ($p_match as $match) {
                $newValue = $replacePatterns[$pi]['replace'];
                foreach ($match['values'] as $number => $value) {
                    $newValue = str_replace('"<'.($number + 1).'>"', $value[1], $newValue);
                }
                [$sampleFileTokens, $lineNum] = self::replaceTokens($sampleFileTokens, $match[0]['start'], $match[0]['end'], $newValue);
                $replacementLines[] = $lineNum;
            }
        }

        return [$sampleFileTokens, $replacementLines];
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
