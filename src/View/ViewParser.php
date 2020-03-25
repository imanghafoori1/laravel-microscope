<?php

namespace Imanghafoori\LaravelSelfTest\View;

use Illuminate\View\ViewName;
use Illuminate\Support\Facades\View;
use TypeHints\Unused\Parser\Action\ParserActionInterface;

class ViewParser
{
    /**
     * @var ParserActionInterface
     */
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
    protected $viewAliases = [
        'View::make(',
        'view(',
        'view->make(',
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
     * @return array
     */
    protected function retrieveViewsFromMethod()
    {
        $views = [];

        $content = $this->action;

        if (! $content) {
            return [];
        }

        if (array_key_exists('view', ($content))) {
            $views[] = $content['view'];

            return $views;
        }

        foreach ($content as $key => $line) {
            foreach ($this->viewAliases as $viewAlias) {
                if (strpos($line, $viewAlias) !== false) {
                    $view = $this->getViewFromLine($line, $viewAlias);
                    if (empty($view)) {
                        $view = $this->getViewFromLine($content[$key + 1], $viewAlias);
                    }
                    $views[] = [
                        'name' => $this->retrieveViewFromLine($view, $viewAlias),
                        'lineNumber' => $key + 1,
                        'directive' => 'view(',
                        'line' => $line
                    ];
                }
            }
        }

        return $views;
    }

    protected function getViewFromLine($line, $viewAlias)
    {
        $line = trim($line);

        if (strpos($line, $viewAlias) === false) {
            return $line;
        }

        return substr($line, strpos($line, $viewAlias) + strlen($viewAlias));
    }

    /**
     * @param  string  $line
     * @param  string  $viewAlias
     *
     * @return string
     */
    protected function retrieveViewFromLine(string $view, string $viewAlias): string
    {
        if (strpos($view, ')') !== false) {
            $view = substr($view, 0, strpos($view, ')'));
        }

        if (($position = strpos($view, ',')) !== false) {
            $view = substr($view, 0, $position);
        }

        foreach ($this->ignoredStrings as $string) {
            $view = str_replace($string, '', $view);
        }

        return trim($view);
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
                    $view = $this->getViewFromLine(substr($line, $position), $bladeDirective);
                    $views[] = [
                        'name' => $this->retrieveViewFromLine($view, $bladeDirective),
                        'file' => $parent_view,
                        'lineNumber' => $lineNumber + 1,
                        'directive' => $bladeDirective,
                        'line' => $line
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
}
