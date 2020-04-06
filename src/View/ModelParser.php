<?php

namespace Imanghafoori\LaravelMicroscope\View;

use ReflectionMethod;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ParseUseStatement;

class ModelParser
{
    /**
     * @var array
     */
    protected $methods = [
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
     * @var array
     */
    protected $ignoredStrings = [
        '(',
        ')',
        ';',
        "'",
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

    protected function getFromLine($line, $viewAlias)
    {
        $line = trim($line);

        if (strpos($line, $viewAlias) === false) {
            return $line;
        }

        return trim(substr($line, strpos($line, $viewAlias) + strlen($viewAlias) + 1), ' (');
    }

    /**
     * @param  string  $parameters
     * @param  int  $n
     *
     * @return string
     */
    protected function retrieveFirstParamValue(string $parameters, $n = 0)
    {
        if (strpos($parameters, ')') !== false) {
            $parameters = substr($parameters, 0, strpos($parameters, ')'));
        }

        if (($position = strpos($parameters, ',')) !== false) {
            $parameters = substr($parameters, 0, $position);
        }

        foreach ($this->ignoredStrings as $string) {
            $parameters = str_replace($string, '', $parameters);
        }

        return trim($parameters);
    }

    /**
     * @param $method
     * @param  array  $content
     * @param  array  $search
     *
     * @return array
     */
    protected function extractParametersValueWithinMethod($ref, $content)
    {
        $tokens = token_get_all('<?php '.implode('',$content));
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

            if (!$this->isRelation($nextToken)) {
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
                    break;
                }

                // in case we have something like:
                //
                // $this->hasMany(Passport::clientModel());
                if ($nextToken == '(') {
                    unset($params[$f]);
                    break;
                }


                $params[$f][] = $nextToken[1];
            }

            foreach ($params as &$param) {
                if ($param[1] ?? null) {
                    $param[0] = ParseUseStatement::expandClassName($param[0],$ref);
                }
            }
            return $params;

        }

        return [];
    }

    protected function extractParametersValueWithinMethod2($method, array $content, array $search)
    {
        $results = [];
        foreach ($content as $key => $line) {
            foreach ($search as $methodName) {
                if (strpos($line, $methodName) === false) {
                    continue;
                }
                if ($method->class == 'Illuminate\Database\Eloquent\Model') {
                    continue;
                }
                $methodParameter = $this->getFromLine($line, $methodName);

                $c = $key;

                while (empty($methodParameter)) {
                    $methodParameter = $this->getFromLine($content[$c], $methodName);
                    $c++;
                }
                unset($c);

                $name = $this->retrieveFirstParamValue($methodParameter);

                if (Str::contains($name, ['$', '::class'])) {
                    continue;
                }

                $results[] = [
                    'name' => $this->retrieveFirstParamValue($methodParameter),
                    'lineNumber' => $method->getStartLine() + $key,
                    'directive' => $methodName,
                    'file' => $method->class,
                    'line' => $line,
                ];
            }
        }

        return $results;
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
        ++$next;
        $nextToken = $tokens[$next];
        if ($nextToken[0] == T_WHITESPACE) {
            ++$next;
            $nextToken = $tokens[$next] ;
        }

        return $nextToken;
    }

    private function isRelation($nextToken)
    {
        $rel = ($nextToken[1] ?? '');
        return (in_array($rel , [
            'hasOne',
            'hasMany',
            'belongsTo',
            'belongsToMany',
            'belongsToOne',
            'hasManyThrough',
            'morphTo',
            'morphToMany',
            'morphedByMany',
        ]));
    }
}
