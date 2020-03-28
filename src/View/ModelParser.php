<?php

namespace Imanghafoori\LaravelSelfTest\View;

use ReflectionMethod;
use Illuminate\Support\Str;

class ModelParser
{
    protected $action;

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

    public function retrieveFromMethod($method)
    {
        $this->action = $method;
        $content = $this->readContent($method);

        if (! $content) {
            return [];
        }

        $search = $this->methods;

        return $this->extractParameterValue($content, $search);
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
     * @param  array  $content
     * @param  array  $search
     *
     * @return array
     */
    protected function extractParameterValue(array $content, array $search)
    {
        $results = [];
        foreach ($content as $key => $line) {
            foreach ($search as $viewAlias) {
                if (strpos($line, $viewAlias) === false) {
                    continue;
                }
                if ($this->action->class == 'Illuminate\Database\Eloquent\Model') {
                    continue;
                }
                $methodParameter = $this->getFromLine($line, $viewAlias);

                $c = $key;

                while (empty($methodParameter)) {
                    $methodParameter = $this->getFromLine($content[$c], $viewAlias);
                    $c++;
                }
                unset($c);

                $name = $this->retrieveFirstParamValue($methodParameter);

                if (Str::contains($name, ['$', '::class'])) {
                    continue;
                }

                $results[] = [
                    'name' => $this->retrieveFirstParamValue($methodParameter),
                    'lineNumber' => $this->action->getStartLine() + $key,
                    'directive' => $viewAlias,
                    'file' => $this->action->class,
                    'line' => $line,
                ];
            }
        }

        return $results;
    }
}
