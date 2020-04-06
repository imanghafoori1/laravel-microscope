<?php

namespace Imanghafoori\LaravelMicroscope\View;

use Illuminate\Support\Facades\View;
use Illuminate\View\ViewName;
use ReflectionMethod;

class ViewParser
{
    protected $action;

    /**
     * @var array
     */
    protected $parent = [];

    /**
     * @var array
     */
    protected $children = [];

    /**
     * @var array
     */
    protected $childrenViews = [];

    /**
     * @var array
     */
    protected $viewAliases = [
        'View::make',
        'view',
        'view->make',
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
     * @var array
     */
    protected $bladeDirectives = [
        '@include(',
        '@includeIf(',
        '@extends(',
        'Blade::include(',
    ];

    /**
     * @var array
     */
    protected $statementBladeDirectives = [
        '@includeWhen(',
        '@includeUnless(',
        '@includeFirst(',
    ];

    public function __construct($action)
    {
        $this->action = $action;
    }

    public function parse()
    {
        $this->parent = $this->retrieveViewsFromMethod();

        if ($this->parent) {
            $this->retrieveChildrenFromNestedViews();
        }

        return $this;
    }

    public function retrieveChildrenFromNestedViews()
    {
        $this->children = $this->loopForNestedViews($this->parent);
    }

    /**
     * @param  array  $children
     */
    public function resolveChildrenHierarchy(array $children)
    {
        collect($children)->each(function ($value, $key) {
            if (is_string($key)) {
                $this->childrenViews[] = $key;
            }

            return $this->resolveChildrenHierarchy($value);
        });
    }

    public function loopForNestedViews($views)
    {
        $generated = [];

        if (! is_array($views)) {
            return $this->loopForNestedViews($this->retrieveNestedViews($views));
        }
        foreach ($views as $view) {
            $generated[$view['name']] = $view + ['children' => $this->loopForNestedViews($view['name'])];
        }

        return $generated;
    }

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

    protected function retrieveViewsFromMethod()
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
     * @param  string  $parent_view
     *
     * @return array
     */
    protected function retrieveNestedViews(string $parent_view)
    {
        $views = [];
        $lines = (array) $this->getViewContent($parent_view);

        foreach ($lines as $lineNumber => $line) {
            foreach ($this->bladeDirectives as $key => $bladeDirective) {
                $positions = $this->getPositionOfBladeDirectives($bladeDirective, $line);
                foreach ($positions as $position) {
                    $view = $this->getFromLine(substr($line, $position), $bladeDirective);
                    $views[] = [
                        'name' => $this->retrieveFirstParamValue($view),
                        'file' => $parent_view.'.blade.php',
                        'lineNumber' => $lineNumber + 1,
                        'directive' => $bladeDirective,
                        'line' => $line,
                    ];
                }
            }
        }

        return $views;
    }

    /**
     * @param  string  $bladeDirective
     * @param  string  $content
     *
     * @return array
     */
    protected function getPositionOfBladeDirectives(string $bladeDirective, $content)
    {
        $positions = [];

        $lastPos = 0;

        while (($lastPos = strpos($content, $bladeDirective, $lastPos)) !== false) {
            $positions[] = $lastPos;
            $lastPos = $lastPos + strlen($bladeDirective);
        }

        return $positions;
    }

    /**
     * @param  string  $view
     *
     * @return string
     */
    public function getViewContent(string $view)
    {
        $view = ViewName::normalize($view);
        try {
            $path = View::getFinder()->find($view);

            return file($path);
        } catch (\InvalidArgumentException $e) {
            return '';
        }
    }

    /**
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
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
                    'directive' => 'view(',
                    'file' => $this->action->class,
                    'line' => $line,
                ];
            }
        }

        return $results;
    }
}
