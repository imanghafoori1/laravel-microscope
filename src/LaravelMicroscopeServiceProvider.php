<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Imanghafoori\LaravelMicroscope\Commands;
use Imanghafoori\LaravelMicroscope\SpyClasses\SpyGate;
use Imanghafoori\LaravelMicroscope\SpyClasses\SpyRouter;
use Imanghafoori\LaravelMicroscope\SpyClasses\ViewsData;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Imanghafoori\LaravelMicroscope\SpyClasses\SpyDispatcher;
use Imanghafoori\LaravelMicroscope\SpyClasses\SpyBladeCompiler;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ConsolePrinterInstaller;

class LaravelMicroscopeServiceProvider extends ServiceProvider
{
    private static $commandNames = [
        Commands\CheckEvents::class,
        Commands\CheckGates::class,
        Commands\CheckRoutes::class,
        Commands\CheckViews::class,
        Commands\CheckPsr4::class,
        Commands\CheckImports::class,
        Commands\CheckAll::class,
        Commands\ClassifyStrings::class,
        Commands\CheckDD::class,
        Commands\CheckEarlyReturns::class,
        Commands\CheckCompact::class,
        Commands\CheckBladeQueries::class,
        Commands\CheckActionComments::class,
        Commands\CheckBadPractice::class,
        Commands\CheckExtractBladeIncludes::class,
        Commands\PrettyPrintRoutes::class,
        Commands\CheckExpansions::class,
    ];

    public function boot()
    {
        (app()['env'] !== 'production') && $this->spyView();

        if (! $this->canRun()) {
            return;
        }

        \Event::listen('microscope.start.command', function () {
            ! defined('microscope_start') && define('microscope_start', microtime(true));
        });

        $this->commands(self::$commandNames);

        ConsolePrinterInstaller::boot();
    }

    public function register()
    {
        (app()['env'] !== 'production') && $this->spyEvents();
        if (! $this->canRun()) {
            return;
        }

        $this->registerCompiler();

        //  $this->loadConfig();

        app()->singleton(ErrorPrinter::class);
        $this->spyRouter();

        // we need to start spying before the boot process starts.

        $command = $_SERVER['argv'][1] ?? null;
        // we spy the router in order to have a list of route files.
        $checkAll = Str::startsWith('check:all', $command);
        ($checkAll || Str::startsWith('check:routes', $command)) && app('router')->spyRouteConflict();
//        ($checkAll || Str::startsWith('check:events', $command)) && $this->spyEvents();
        ($checkAll || Str::startsWith('check:gates', $command)) && $this->spyGates();
    }

    private function spyRouter()
    {
        $router = new SpyRouter(app('events'), app());
        $this->app->singleton('router', function ($app) use ($router) {
            return $router;
        });
        Route::swap($router);
    }

    private function spyGates()
    {
        $this->app->singleton(GateContract::class, function ($app) {
            return new SpyGate($app, function () use ($app) {
                return call_user_func($app['auth']->userResolver());
            });
        });
    }

    private function spyEvents()
    {
        app()->booting(function () {
            $this->app->singleton('events', function ($app) {
                return (new SpyDispatcher($app))->setQueueResolver(function () use ($app) {
                    return $app->make(QueueFactoryContract::class);
                });
            });
            Event::clearResolvedInstance('events');
        });
    }

    public function spyView()
    {
        app()->singleton('microscope.views', ViewsData::class);

        \View::creator('*', function (View $view) {
            resolve('microscope.views')->add($view);
        });

        app()->terminating(function () {
            $spy = resolve('microscope.views');
            if (! $spy->main || Str::startsWith($spy->main->getName(), ['errors::'])) {
                return;
            }
            $action = $this->getActionName();

            $uselessVars = array_keys(array_diff_key($spy->getMainVars(), $spy->readTokenizedVars()));
            $viewName = $spy->main->getName();

            if ($uselessVars) {
                \Log::info('Laravel Microscope - The view file "'.$viewName.'"');
                \Log::info('At "'.$action.'" has some unused variables passed to it: ');
                \Log::info($uselessVars);
                \Log::info('If you do not see these variables passed a in controller, look in view composers.');
            }
        });
    }

    private function loadConfig()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-microscope');
    }

    private function canRun()
    {
        return $this->app->runningInConsole() && app()['env'] !== 'production';
    }

    public function getActionName()
    {
        $action = '';
        if ($cRoute = \Route::getCurrentRoute()) {
            $action = $cRoute->getActionName();
        }

        return $action;
    }

    private function registerCompiler()
    {
        $this->app->singleton('microscope.blade.compiler', function () {
            return new SpyBladeCompiler($this->app['files'], $this->app['config']['view.compiled']);
        });
    }
}
