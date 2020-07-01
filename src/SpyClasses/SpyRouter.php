<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Routing\Router;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;

class SpyRouter extends Router
{
    public $routePaths = [];

    /**
     * @var \Imanghafoori\LaravelMicroscope\SpyClasses\SpyRouteCollection
     */
    private $routesSpy = null;

    public function spyRouteConflict()
    {
        $this->routesSpy = $this->routes = new SpyRouteCollection();
    }

    protected function loadRoutes($routes)
    {
        // This is needed to collect the route paths to tokenize and run inspections.
        ! ($routes instanceof Closure) && $this->routePaths[] = $routes;

        parent::loadRoutes($routes);
    }

    public function updateGroupStack(array $attributes)
    {
        if (isset($attributes['middlewares'])) {
            $err = "['middlewares' => ...] key passed to Route::group(...) is not correct.";
            app(ErrorPrinter::class)->route(
                null,
                'Incorrect \'middlewares\' key.',
                $err,
                $info['file'] ?? '',
                $info['line'] ?? 1
            );
        }
        parent::updateGroupStack($attributes);

        $e = $this->groupStack;
        $new_attr = end($e);

        $i = 2;
        while (
            ($info = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $i + 1)[$i])
            &&
            $this->isExcluded($info)
        ) {
            $i++;
        }
        $ns = $new_attr['namespace'] ?? null;
        $dir = (NamespaceCorrector::getRelativePathFromNamespace($ns));

        if ($ns && $ns !== $dir && !is_dir($dir)) {
            $err = "['namespace' => "."'".$attributes['namespace']. '\'] passed to Route::group(...) is not correct.';
            app(ErrorPrinter::class)->route(
                null,
                'Incorrect namespace.',
                $err,
                $info['file'] ?? '',
                $info['line'] ?? 1
            );
        }
    }

    private function isExcluded($info)
    {
        return Str::startsWith(($info['file'] ?? ''), [
            base_path('vendor'.DIRECTORY_SEPARATOR.'laravel'),
            base_path('vendor'.DIRECTORY_SEPARATOR.'imanghafoori'),
        ]);
    }

    public function addRoute($methods, $uri, $action)
    {
        $i = 2;
        while (
            ($info = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $i + 1)[$i])
            &&
            $this->isExcluded($info)
        ) {
            $i++;
        }
        $routeObj = $this->createRoute($methods, $uri, $action);
        $this->routesSpy && $this->routesSpy->addCallSiteInfo($routeObj, $info);

        return $this->routes->add($routeObj);
    }
}
