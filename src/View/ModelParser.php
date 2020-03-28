<?php

namespace Imanghafoori\LaravelSelfTest\View;

use ReflectionMethod;
use Illuminate\Support\Str;

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

    public function retrieveFromMethod($method)
    {
        $content = $this->readContent($method);

        if (! $content) {
            return [];
        }

        $search = $this->methods;

        return $this->extractParametersValueWithinMethod($method, $content, $search);
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
    protected function extractParametersValueWithinMethod($method, array $content, array $search)
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
}
