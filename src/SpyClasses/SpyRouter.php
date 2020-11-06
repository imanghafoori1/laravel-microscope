<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use Closure;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

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
        parent::updateGroupStack($attributes);

        $e = $this->groupStack;
        $newAttr = end($e);

        $i = 2;
        while (
            ($info = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $i + 1)[$i])
            &&
            $this->isExcluded($info)
        ) {
            $i++;
        }
        $ns = $newAttr['namespace'] ?? null;
        $dir = NamespaceCorrector::getRelativePathFromNamespace($ns);

        if (isset($attributes['middlewares'])) {
            $err = "['middlewares' => ...] key passed to Route::group(...) is not correct.";
            $this->routeError($info, $err, "Incorrect 'middlewares' key.");
        }

        if ($ns && isset($attributes['namespace']) && ! is_dir($dir) && \str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $ns) !== $dir) {
            $err = "['namespace' => "."'".$attributes['namespace'].'\'] passed to Route::group(...) is not correct.';
            $this->routeError($info, $err, 'Incorrect namespace.');
        }
    }

    public function routeError($info, $err, $msg)
    {
        app(ErrorPrinter::class)->route(
            null,
            $msg,
            $err,
            $info['file'] ?? '',
            $info['line'] ?? 1
        );
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
