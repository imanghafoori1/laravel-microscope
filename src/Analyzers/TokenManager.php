<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class TokenManager
{
    public static function removeTokens($tokens, $from, $to, $at)
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

    public static function getPrevToken($tokens, $i)
    {
        $i--;
        $token = $tokens[$i];
        while ($token[0] == T_WHITESPACE || $token[0] == T_COMMENT) {
            $i--;
            $token = $tokens[$i];
        }

        return [$token, $i];
    }

    public static function forwardTo($tokens, $i, $tokenType)
    {
        $i++;
        $nextToken = $tokens[$i] ?? '_';
        while (! \in_array($nextToken[0], $tokenType)) {
            $i++;
            $nextToken = $tokens[$i] ?? [null, null];
        }

        return [$nextToken, $i];
    }

    public static function readBodyBack(&$tokens, $i)
    {
        $body = [];
        $level = 0;
        while (true) {
            [$token, $i] = self::getPrevToken($tokens, $i);

            if (\in_array($token[0], [']', ')', '}'])) {
                $level--;
            }

            $isOpening = \in_array($token[0], ['[', '(', '{', T_CURLY_OPEN]);

            if ($level == 0 && $isOpening) {
                break;
            }

            if ($isOpening) {
                $level++;
            }

            $body[] = $token;
        }

        return [$body, $i];
    }

    public static function readBody(&$tokens, $i, $until = '}')
    {
        $body = [];
        $level = 0;
        while (true) {
            $i++;
            $nextToken = $tokens[$i] ?? '_';

            if ($nextToken == '_') {
                break;
            }

            if ($level == 0 && $nextToken[0] == $until) {
                break;
            }

            if (\in_array($nextToken[0], ['[', '(', '{', T_CURLY_OPEN])) {
                $level++;
            }

            if (\in_array($nextToken[0], [']', ')', '}'])) {
                $level--;
            }

            $body[] = $nextToken;
        }

        return [$body, $i];
    }

    public static function readBackUntil(&$tokens, $i, $chars = ['}'])
    {
        $orphanBlock = [];
        while (true) {
            [$token, $i] = self::getPrevToken($tokens, $i);

            $depth = 0;
            if (\in_array($token[0], $chars)) {
                [$ifBody, $openIfIndex] = self::readBodyBack($tokens, $i);
                [, $closeParenIndex] = self::getPrevToken($tokens, $openIfIndex);
                [$condition, $openParenIndex] = self::readBodyBack($tokens, $closeParenIndex);
                [$ownerOfClosing] = self::getPrevToken($tokens, $openParenIndex);

                if ($ownerOfClosing[0] == T_IF) {
                    break;
                } else {
                    return [null, null];
                }
            }

            if ($token[0] == '{') {
                $depth--;

                if ($depth === -1) {
                    return [null, null];
                }
            }

            $orphanBlock[] = $token;
        }

        return [[$ifBody, [$openIfIndex, $i]], [$condition, [$openParenIndex, $closeParenIndex]], $orphanBlock, $i];
    }

    public static function isEqual($expectedToken, $actualToken)
    {
        return $expectedToken[0] == $actualToken[0] && ($expectedToken[1] ?? '') == ($actualToken[1] ?? '');
    }
}
