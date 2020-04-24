<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class FunctionCall
{
    protected static function getNextToken($tokens, $i)
    {
        $i++;
        $nextToken = $tokens[$i] ?? '_';
        if ($nextToken[0] == T_WHITESPACE) {
            $i++;
            $nextToken = $tokens[$i] ?? null;
        }

        return [$nextToken, $i];
    }

    protected static function getPrevToken($tokens, $i)
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
        $className && ($tokens[] = [T_STRING, $className]);

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

    /**
     * @param  array  $tokens
     * @param  int  $i the index of the "(" token.
     *
     * @return array
     */
    public static function readParameters(&$tokens, $i)
    {
        $params = [];
        $p = 0;
        $level = 1;
        while (true) {
            [$nextToken, $i] = self::getNextToken($tokens, $i);

            if (in_array($nextToken, ['[', '(', '{'])) {
                $level++;
            }

            if (in_array($nextToken, [']', ')', '}'])) {
                $level--;
            }

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

    private static function isEqual($expectedToken, $actualToken): bool
    {
        return $expectedToken[0] == $actualToken[0] && ($expectedToken[1] ?? '') == ($actualToken[1] ?? '');
    }
}
