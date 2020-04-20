<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class MethodParser
{
    public static function extractParametersValueWithinMethod($tokens, $methodNames)
    {
        $relations = [];
        $i = 0;
        while (true) {
            // goes until the end of tokens
            if (! isset($tokens[$i])) {
                break;
            }

            // discover public function
            if (! self::isPublicMethodDeclaration($tokens, $i)) {
                $i++;
                continue;
            }
            $i = $i + 2;
            $method = $tokens[$i];

            $callingMethod = self::containsMethodCall($tokens, $method, $i, $methodNames);

            if (! $callingMethod) {
                continue;
            }

            self::getNextToken($tokens, $i);

            // collect parameters
            $callingMethod['params'] = self::readPassedParameters($tokens, $i);

            $relations[] = $callingMethod;
        }

        return $relations;
    }

    private static function is($type, $token)
    {
        return $token[0] == $type[0] and $token[1] == $type[1];
    }

    /**
     * @param  array  $tokens
     * @param  int  $i
     *
     * @return mixed
     */
    protected static function getNextToken($tokens, &$i)
    {
        $i++;
        if (! isset($tokens[$i])) {
            return;
        }
        $nextToken = $tokens[$i];
        if ($nextToken[0] == T_WHITESPACE) {
            $i++;
            $nextToken = $tokens[$i];
        }

        return $nextToken;
    }

    private static function isMethodName($token, $methods)
    {
        return ($token[0] == T_STRING) && in_array($token[1], $methods);
    }

    /**
     * @param  $tokens
     * @param  $i
     *
     * @return bool
     */
    protected static function isPublicMethodDeclaration($tokens, $i)
    {
        // it is "function" keyword && not private && ensure is not an anonymous function
        return $tokens[$i][0] == T_FUNCTION && ! in_array($tokens[$i][0], [T_PRIVATE, T_PROTECTED]) && $tokens[$i + 2][0] == T_STRING;
    }

    /**
     * @param $tokens
     * @param $i
     *
     * @return array
     */
    protected static function readPassedParameters($tokens, &$i)
    {
        $calls = 1;
        $paramCount = 0;
        $collect = true;
        $params = [];
        while (true) {
            $token = self::getNextToken($tokens, $i);
            // in case we have something like:
            // self::hasMany(Passport::clientModel());
            if ($token == '(') {
                // Forget what we have collected as a parameter
                $params[$paramCount] = [];
                $calls++;
                // and stop collecting until we reach a the next parameter or end.
                $collect = false;
                continue;
            }

            if ($token == ')') {
                $calls--;

                // end of method call for hasMany(...)
                if ($calls == 0) {
                    $params[$paramCount] = implode('', $params[$paramCount]);
                    break;
                } else {
                    continue;
                }
            }

            if (($token[0] == T_DOUBLE_COLON && $tokens[$i + 1][0] != T_CLASS)
                || $token[0] == T_VARIABLE
                || $token[0] == T_OBJECT_OPERATOR
                || $token[0] == '"') {
                // Forget what we have collected as a parameter
                $params[$paramCount] = [];
                // and stop collecting until we reach a the next parameter or end.
                $collect = false;
                continue;
            }

            if ($token == ',') {
                // we are dealing the the next parameter of hasMany
                if ($calls == 1) {
                    // self::hasMany(Passport::clientModel(1, 2));
                    // For commas within inline method calls,
                    // we do not want to count up or anything
                    $params[$paramCount] = implode('', $params[$paramCount]);
                    $paramCount++;
                    $collect = true;
                }
                continue;
            }

            // When we reach ::class
            if ($token[0] == T_CLASS) {
                // remove '::' from the end of the array.
                array_pop($params[$paramCount]);
                continue;
            }

            if ($collect) {
                $params[$paramCount][] = $token[1] ?? $token[0];
            }
        }

        return $params;
    }

    /**
     * @param  array  $tokens
     * @param  array  $method
     * @param  int  $i
     *
     * @param $relations
     *
     * @return array|bool
     */
    protected static function containsMethodCall($tokens, $method, &$i, $relations)
    {
        $relation = [
            'name' => $method[1],
            'line' => $method[2],
            'hasReturn' => false,
        ];
        // continues ahead
        while (true) {
            $token = self::getNextToken($tokens, $i);

            if (self::is([T_VARIABLE, '$this'], $token)) {
                $token = self::getNextToken($tokens, $i);
                if (! self::is([T_OBJECT_OPERATOR, '->'], $token)) {
                    continue;
                }

                $token = self::getNextToken($tokens, $i);
                if (! self::isMethodName($token, $relations)) {
                    continue;
                }

                $relation['type'] = $token[1];

                return $relation;
            } elseif ($token == '}') {
                return false;
            } elseif ($token[0] == T_RETURN) {
                $relation['hasReturn'] = true;
            }
        }

        return false;
    }
}
