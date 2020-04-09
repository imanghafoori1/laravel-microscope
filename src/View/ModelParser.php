<?php

namespace Imanghafoori\LaravelMicroscope\View;

use Imanghafoori\LaravelMicroscope\ParseUseStatement;
use ReflectionMethod;

class ModelParser
{
    /**
     * @var array
     */
    protected $methods
        = [
            '->hasMany',
            '->hasOne',
            '->belongsTo',
            '->belongsToMany',
            '->belongsToOne',
            '->hasManyThrough',
            '->morphTo',
            '->morphToMany',
            '->morphedByMany',
        ];

    /**
     * @param  \ReflectionMethod  $method
     *
     * @return array
     */
    protected function readContent(ReflectionMethod $method)
    {
        $start = $method->getStartLine() - 1;
        $length = $method->getEndLine() - $method->getStartLine() + 1;

        return array_slice(file($method->getFileName()), $start, $length);
    }

    public function retrieveFromMethod($method, \ReflectionClass $ref)
    {
        $content = $this->readContent($method);

        if (! $content) {
            return [];
        }

        return $this->extractParametersValueWithinMethod($ref, $content);
    }

    /**
     * @param $ref
     * @param  array  $content
     *
     * @return array
     */
    protected function extractParametersValueWithinMethod($ref, $content)
    {
        $tokens = token_get_all('<?php '.implode('', $content));
        foreach ($tokens as $i => $token) {
            if (! is_array($token)) {
                continue;
            }

            $next = $i;
            $relation = [];

            if (! $this->isThis($token)) {
                continue;
            }

            $relation[] = '$this';
            $nextToken = $this->getNextToken($tokens, $next);

            if ($this->isArrow($nextToken)) {
                $relation[] = '->';
            }

            $nextToken = $this->getNextToken($tokens, $next);

            if (! $this->isRelation($nextToken)) {
                continue;
                $relation[] = 'relation';
                $relation['relation'] = $nextToken[1];
            }

            $nextToken = $this->getNextToken($tokens, $next);

            if ($nextToken == '(') {
                $relation[] = '(';
            }

            $params = [];

            $f = 0;
            while (true) {
                $nextToken = $this->getNextToken($tokens, $next);

                if ($nextToken == ',' || $nextToken == ')') {
                    $f++;
                    // for now we only collect the first parameter
                    // so we break; here instead of 'continue;'
                    break;
                }

                // in case we have something like:
                // $this->hasMany(Passport::clientModel());
                if ($nextToken == '(') {
                    unset($params[$f]);
                    break;
                }

                if (($nextToken[1] ?? null) == 'class') {
                    // remove '::' from the end of the array.
                    array_pop($params[$f]);
                    break;
                }

                $params[$f][] = $nextToken[1];
            }

            foreach ($params as &$param) {
                $tmp = implode('', $param);

                if ($tmp[0] == "'" || $tmp[0] == '"') {
                    // in case a hard-coded string is passed.
                    $tmp = trim($tmp, '\'\"');
                } else {
                    // in case the class is passed by ::class
                    $tmp = ParseUseStatement::expandClassName($tmp, $ref);
                }

                $param[0] = $tmp;
            }

            return $params;
        }

        return [];
    }

    /**
     * @param  array  $token
     *
     * @return bool
     */
    protected function isThis(array $token)
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
     * @param $next
     *
     * @return mixed
     */
    protected function getNextToken(array $tokens, &$next)
    {
        $next++;
        $nextToken = $tokens[$next];
        if ($nextToken[0] == T_WHITESPACE) {
            $next++;
            $nextToken = $tokens[$next];
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
            'morphTo',
            'morphToMany',
            'morphedByMany',
        ]);
    }
}
