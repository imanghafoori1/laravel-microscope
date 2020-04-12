<?php

namespace Imanghafoori\LaravelMicroscope\View;

class ModelParser
{
    function extractParametersValueWithinMethod($tokens)
    {
        $relations = [];
        $i = 0;
        while(true) {
            if (! isset($tokens[$i])) {
                break;
            }
            $token = $tokens[$i];

            // discover public function
            if ($tokens[$i][0] == T_FUNCTION && ! in_array($token[0], [T_PRIVATE, T_PROTECTED]) && $tokens[$i + 2][0] == T_STRING) {
                $isRelation = true;
                $i = $i + 2;
                $methodName = $tokens[$i];

                $relation = [
                    'name' => $methodName[1],
                    'line' => $methodName[2],
                    'hasReturn' => false,
                ];
                $i = $i + 1;
            } else {
                $i++;
                continue;
            }

            if (! $isRelation) {
                continue;
            }

            // continues ahead
            while(true) {
                $token = $this->getNextToken($tokens, $i);

                if ($this->isThis($token)) {
                    $token = $this->getNextToken($tokens, $i);
                    if ($this->isArrow($token)) {
                        $token = $this->getNextToken($tokens, $i);
                        if ($this->isRelation($token)) {
                            $relationType = $token[1];
                            $isRelation = true;
                            break;
                        }
                    }
                    $isRelation = false;
                    break;
                } elseif($token == '}') {
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

            $params = [];
            // collect parameters
            $c = 0;
            $collect = true;
            while (true) {
                $token = $this->getNextToken($tokens, $i);
                // in case we have something like:
                // $this->hasMany(Passport::clientModel());
                if ($token == '(') {
                    // forget
                    unset($params[$c]);
                    $collect = false;
                    continue;
                }
                if ($token == ')') {
                    $collect = true;
                    continue;
                }
                if (! $collect) {
                    continue;
                }

                if ($token == ',') {
                    if (! isset($params[$c])) {
                        $params[$c] = [];
                    }
                    $params[$c] = implode('', $params[$c]);
                    $c++;
                    continue;
                }

                if (($token[1] ?? null) == 'class') {
                    // remove '::' from the end of the array.
                    array_pop($params[$c]);
                    continue;
                }

                // in case of method chain on the relation...
                // $this->hasMany(...)->orderBy(...);
                if ($token == ';' || $token[0] == T_OBJECT_OPERATOR) {
                    if (! isset($params[$c])) {
                        $params[$c] = [];
                    }
                    $params[$c] = implode('', $params[$c]);
                    break;
                }

                if ($collect) {
                    $params[$c][] = $token[1];
                }
            }
            $relation['params'] = $params;
            $relation['type'] = $relationType;

            $relations[] = $relation;
        }

        return $relations;
    }

    /**
     * @param  array  $token
     *
     * @return bool
     */
    protected function isThis($token)
    {
        return $token[0] == T_VARIABLE and $token[1] == '$this';
    }

    /**
     * @param $nextToken
     *
     * @return bool
     */
    protected function isArrow($nextToken)
    {
        return $nextToken[0] == T_OBJECT_OPERATOR and $nextToken[1] == '->';
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
}
