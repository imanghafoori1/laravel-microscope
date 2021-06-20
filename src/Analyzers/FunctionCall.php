<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class FunctionCall
{
    public static function isSolidString($tokens)
    {
        [$nextToken,] = TokenManager::getNextToken($tokens, 0);

        // we make sure that the string is not concatinated.
        return ($tokens[0][0] == T_CONSTANT_ENCAPSED_STRING) && ($nextToken !== '.');
    }

    public static function isGlobalCall($funcName, &$tokens, $i)
    {
        $expectedTokens = [
            ['('],
            [T_STRING, $funcName],
        ];

        if (empty($indexes = self::checkTokens($expectedTokens, $tokens, $i))) {
            return null;
        }

        $index = array_pop($indexes);
        [$prev, $p2] = TokenManager::getPrevToken($tokens, $index);
        $ops = [T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NEW, T_FUNCTION];

        if (\in_array($prev[0], $ops)) {
            return null;
        }

        return $index;
    }

    public static function isStaticCall($methodName, &$tokens, $i, $className = null)
    {
        $expectedTokens = [
            ['('],
            [T_STRING, $methodName],
            [T_DOUBLE_COLON, '::'],
        ];
        $className && ($expectedTokens[] = [T_STRING, $className]);

        return self::checkTokens($expectedTokens, $tokens, $i);
    }

    public static function isMethodCallOnThis($methodName, &$tokens, $i)
    {
        $expectedTokens = [
            ['('],
            [T_STRING, $methodName],
            [T_OBJECT_OPERATOR, '->'],
            [T_VARIABLE, '$this'],
        ];

        return self::checkTokens($expectedTokens, $tokens, $i);
    }

    public static function checkTokens($expectedTokens, &$tokens, $j)
    {
        if ($tokens[$j][0] != '(') {
            return [];
        }

        array_shift($expectedTokens); // remove ( from the array.

        $results = [];
        foreach ($expectedTokens as $i => $expectedToken) {
            try {
                [$actualToken, $j] = TokenManager::getPrevToken($tokens, $j);
            } catch (\Throwable $e) {
                return [];
            }
            if (! TokenManager::isEqual($expectedToken, $actualToken)) {
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
            [$nextToken, $i] = TokenManager::getNextToken($tokens, $i);

            $level = self::level($nextToken, $level);

            if ($level == 0 && $nextToken == ')') {
                break;
            }

            // Fixes: https://github.com/imanghafoori1/laravel-microscope/issues/135
            // To avoid infinite loop in case of wrong syntax
            if ($nextToken == '_') {
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

    /* public static function readConditions(&$tokens, $i)
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
     }*/

    private static function level($nextToken, $level)
    {
        if (\in_array($nextToken[0], ['[', '(', '{', T_CURLY_OPEN])) {
            $level++;
        }

        if (\in_array($nextToken[0], [']', ')', '}'])) {
            $level--;
        }

        return $level;
    }
}
