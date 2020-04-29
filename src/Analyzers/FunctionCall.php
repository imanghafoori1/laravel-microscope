<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class FunctionCall
{
    static function getNextToken($tokens, $i)
    {
        $i++;
        $nextToken = $tokens[$i] ?? '_';
        if ($nextToken[0] == T_WHITESPACE) {
            $i++;
            $nextToken = $tokens[$i] ?? null;
        }

        return [$nextToken, $i];
    }

    static function forwardTo($tokens, $i, $tokenType)
    {
        $i++;
        $nextToken = $tokens[$i] ?? '_';
        while (! in_array($nextToken, $tokenType)) {
            $i++;
            $nextToken = $tokens[$i] ?? null;
        }

        return [$nextToken, $i];
    }

    static function getPrevToken($tokens, $i)
    {
        $i--;
        $token = $tokens[$i];
        if ($token[0] == T_WHITESPACE) {
            $i--;
            $token = $tokens[$i];
        }

        return [$token, $i];
    }

    static function isSolidString($tokens)
    {
        [$nextToken, $i] = self::getNextToken($tokens, 0);
        return ($tokens[0][0] == T_CONSTANT_ENCAPSED_STRING) && ($nextToken !== '.');
    }

    static function isGlobalCall($funcName, &$tokens, $i)
    {
        $expectedTokens = [
            ['('],
            [T_STRING, $funcName],
        ];

        if (empty($indexes = self::checkTokens($expectedTokens, $tokens, $i))) {
            return null;
        }

        $index = array_pop($indexes);
        [$prev, $p2] = self::getPrevToken($tokens, $index);
        $ops = [T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NEW, T_FUNCTION];

        if (in_array($prev[0], $ops)) {
            return null;
        }

        return $index;
    }

    static function isStaticCall($methodName, &$tokens, $i, $className = null)
    {
        $expectedTokens = [
            ['('],
            [T_STRING, $methodName],
            [T_DOUBLE_COLON, '::'],
        ];
        $className && ($expectedTokens[] = [T_STRING, $className]);

        return self::checkTokens($expectedTokens, $tokens, $i);
    }

    static function isMethodCallOnThis($methodName, &$tokens, $i)
    {
        $expectedTokens = [
            ['('],
            [T_STRING, $methodName],
            [T_OBJECT_OPERATOR, '->'],
            [T_VARIABLE, '$this'],
        ];

        return self::checkTokens($expectedTokens, $tokens, $i);
    }

    static function checkTokens($expectedTokens, &$tokens, $j)
    {
        if ($tokens[$j][0] != '(') {
            return [];
        }
        array_shift($expectedTokens); // remove ( from the array.


        $results = [];
        foreach ($expectedTokens as $i => $expectedToken) {
            [$actualToken, $j] = self::getPrevToken($tokens, $j);
            if (! self::isEqual($expectedToken, $actualToken)) {
                return [];
            }
            $results[] = $j;
        }

        return $results;
    }

    public static function readParameters(&$tokens, $i)
    {
        $params = [];
        $p = 0;
        $level = 1;
        while (true) {
            [$nextToken, $i] = self::getNextToken($tokens, $i);

            $level = self::level($nextToken, $level);

            if ($level == 0 && $nextToken == ')') {
                break;
            }

            if ($level == 1 && $nextToken == ',') {
                $p++;
                continue;
            }

            $params[$p][] = $nextToken;
        }

        return $params;
    }

    public static function readConditions(&$tokens, $i)
    {
        $params = [];
        $level = 1;
        while (true) {
            [$nextToken, $i] = self::getNextToken($tokens, $i);

            $level = self::level($nextToken, $level);

            if ($level == 0 && $nextToken == ')') {
                break;
            }

            $params[] = $nextToken;
        }

        return [$params, $i];
    }

    public static function readBackUntil(&$tokens, $i, $char = '}')
    {
        $orphanBlock = [];
        while (true) {

            [$token, $i] = self::getPrevToken($tokens, $i);


            $depth = 0;
            if ($token == $char) {
                [$ifBody, $openIfIndex] = FunctionCall::readBodyBack($tokens, $i);
                [, $closeParenIndex] = FunctionCall::getPrevToken($tokens, $openIfIndex);
                [$condition, $openParenIndex] = FunctionCall::readBodyBack($tokens, $closeParenIndex);
                [$ownerOfClosing, $ifIndex] = FunctionCall::getPrevToken($tokens, $openParenIndex);

                if ($ownerOfClosing[0] == T_IF) {
                    break;
                } else {
                    return [null, null];
                }
            }

            if ($token == '{') {
                $depth--;

                if ($depth === -1) {
                    return [null,null];
                }
            }

            $orphanBlock[] = $token;
        }

        return [[$ifBody, [$openIfIndex, $i]], [$condition, [$openParenIndex, $closeParenIndex]], $orphanBlock, $i];
    }

    public static function readBodyBack(&$tokens, $i)
    {
        $body = [];
        $level = 0;
        while (true) {
            [$token, $i] = self::getPrevToken($tokens, $i);

            if (in_array($token, [']', ')', '}'])) {
                $level--;
            }

            $isOpening = in_array($token, ['[', '(', '{']);

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

    public static function readBody(&$tokens, $i)
    {
        $body = [];
        $level = 0;
        while (true) {
            [$nextToken, $i] = self::getNextToken($tokens, $i);

            if ($level == 0 && $nextToken == '}') {
                break;
            }

            $level = self::level($nextToken, $level);

            if ($nextToken == '_') {
                break;
            }

            $body[] = $nextToken;
        }

        return [$body, $i];
    }

    private static function isEqual($expectedToken, $actualToken)
    {
        return $expectedToken[0] == $actualToken[0] && ($expectedToken[1] ?? '') == ($actualToken[1] ?? '');
    }

    private static function level($nextToken, $level)
    {
        if (in_array($nextToken, ['[', '(', '{'])) {
            $level++;
        }

        if (in_array($nextToken, [']', ')', '}'])) {
            $level--;
        }

        return $level;
    }
}
