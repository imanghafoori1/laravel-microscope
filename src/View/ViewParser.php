<?php

namespace Imanghafoori\LaravelMicroscope\View;

use ReflectionMethod;

class ViewParser
{
    protected $action;

    /**
     * @var array
     */
    protected $viewAliases = [
        'View::make',
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

    public function __construct($action)
    {
        $this->action = $action;
    }

    protected function readContent(ReflectionMethod $method)
    {
        $start = $method->getStartLine() - 1;
        $length = $method->getEndLine() - $method->getStartLine() + 1;

        return array_slice(file($method->getFileName()), $start, $length);
    }

    public function retrieveViewsFromMethod()
    {
        $content = $this->readContent($this->action);

        if (! $content) {
            return [];
        }
        $search = $this->viewAliases;

        return $this->extractParameterValue($content, $search);
    }

    protected function getFromLine($line, $viewAlias)
    {
        $line = trim($line);

        $pos = strpos($line, $viewAlias);
        if ($pos === false) {
            return $line;
        }

        // to exclude commented lines...
        $c1 = strpos($line, '//');
        $c1 = ($c1 === false) ? 10000 : $c1;

        $c2 = strpos($line, '*');
        $c2 = ($c2 === false) ? 10000 : $c2;

        // to exclude commented lines...
        if ($c1 < $pos || $c2 < $pos) {
            return $line;
        }

        return trim(substr($line, $pos + strlen($viewAlias) + 1), ' (');
    }

    protected function retrieveFirstParamValue($parameters)
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
                $methodParameter = $this->getFromLine($line, $viewAlias);

                $c = $key;

                /**
                 *   For such a case when the are multiple lines of white space:.
                 *
                 * view(
                 *
                 *  'welcome'
                 *
                 * );
                 *
                 *   We will loop until we find the parameter.
                 */
                while (empty($methodParameter)) {
                    $methodParameter = $this->getFromLine($content[$c], $viewAlias);
                    $c++;
                }
                unset($c);
                $results[] = [
                    'name' => $this->retrieveFirstParamValue($methodParameter),
                    'lineNumber' => $this->action->getStartLine() + $key,
                    'directive' => $viewAlias,
                    'file' => $this->action->getFileName(),
                    'line' => $line,
                ];
            }
        }

        return $results;
    }
}
