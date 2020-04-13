<?php

namespace Imanghafoori\LaravelMicroscope\View;

class ModelParser
{
    public function extractParametersValueWithinMethod($tokens)
    {
        $relations = [];
        $i = 0;
        while (true) {
            if (! isset($tokens[$i])) {
                break;
            }

            // discover public function
            if (! $this->isPublicMethodDeclaration($tokens, $i)) {
                $i++;
                continue;
            }
            $isRelation = true;
            $i = $i + 2;
            $method = $tokens[$i];

            $relation = [
                'name' => $method[1],
                'line' => $method[2],
                'hasReturn' => false,
            ];
            $i++;

            if (! $isRelation) {
                continue;
            }

            // continues ahead
            while (true) {
                $token = $this->getNextToken($tokens, $i);

                if ($this->is([T_VARIABLE, '$this'], $token)) {
                    $token = $this->getNextToken($tokens, $i);
                    if ($this->is([T_OBJECT_OPERATOR, '->'], $token)) {
                        $token = $this->getNextToken($tokens, $i);
                        if ($this->isRelation($token)) {
                            $relation['type'] = $token[1];
                            $isRelation = true;
                            break;
                        }
                    }
                    $isRelation = false;
                    break;
                } elseif ($token == '}') {
                    $isRelation = false;
                    break;
                } elseif ($token[0] == T_RETURN) {
                    $relation['hasReturn'] = true;
                }
            }

            if (! $isRelation) {
                continue;
            }

            $token = $this->getNextToken($tokens, $i);

            if ($token !== '(') {
                $isRelation == false;
                continue;
            }

            // collect parameters
            [$params, $i] = $this->readPassedParameters($tokens, $i);
            $relation['params'] = $params;

            $relations[] = $relation;
        }

        return $relations;
    }

    private function is($type, $token)
    {
        return $token[0] == $type[0] and $token[1] == $type[1];
    }

    /**
     * @param  array  $tokens
     * @param $i
     *
     * @return mixed
     */
    protected function getNextToken(array $tokens, &$i)
    {
        $i++;
        if (! isset($tokens[$i])) {
            return null;
        }
        $nextToken = $tokens[$i];
        if ($nextToken[0] == T_WHITESPACE) {
            $i++;
            $nextToken = $tokens[$i];
        }

        return $nextToken;
    }

    private function isRelation($nextToken)
    {
        $rel = ($nextToken[1] ?? '');

        return in_array($rel, [
            'hasOne',
            'hasMany',
            'belongsTo',
            'belongsToMany',
            'belongsToOne',
            'hasManyThrough',
            // This can work even with no parameter, so we ignore it.
            // 'morphTo',
            'morphToMany',
            'morphedByMany',
        ]);
    }

    /**
     * @param  $tokens
     * @param  $i
     *
     * @return bool
     */
    protected function isPublicMethodDeclaration($tokens, $i)
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
    protected function readPassedParameters($tokens, $i)
    {
        $calls = 1;
        $paramCount = 0;
        $collect = true;
        $params = [];
        while (true) {
            $token = $this->getNextToken($tokens, $i);
            // in case we have something like:
            // $this->hasMany(Passport::clientModel());
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

            if (($token[0] == T_DOUBLE_COLON && $tokens[$i + 1][0] != T_CLASS) || $token[0] == T_VARIABLE || $token[0] == T_OBJECT_OPERATOR) {
                // Forget what we have collected as a parameter
                $params[$paramCount] = [];
                // and stop collecting until we reach a the next parameter or end.
                $collect = false;
                continue;
            }

            if ($token == ',') {
                // we are dealing the the next parameter of hasMany
                if ($calls == 1) {
                    // $this->hasMany(Passport::clientModel(1, 2));
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
                $params[$paramCount][] = $token[1];
            }
        }

        return [$params, $i];
    }
}
