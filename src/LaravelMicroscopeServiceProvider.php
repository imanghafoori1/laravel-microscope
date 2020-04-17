<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Imanghafoori\LaravelMicroscope\Commands\CheckAll;
use Imanghafoori\LaravelMicroscope\Commands\CheckEvents;
use Imanghafoori\LaravelMicroscope\Commands\CheckGates;
use Imanghafoori\LaravelMicroscope\Commands\CheckImports;
use Imanghafoori\LaravelMicroscope\Commands\CheckPsr4;
use Imanghafoori\LaravelMicroscope\Commands\CheckRoutes;
use Imanghafoori\LaravelMicroscope\Commands\CheckViews;
use Imanghafoori\LaravelMicroscope\Commands\ClassifyStrings;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class LaravelMicroscopeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (! $this->canRun()) {
            return;
        }

        $this->commands([
            CheckEvents::class,
            CheckGates::class,
            CheckRoutes::class,
            CheckViews::class,
            CheckPsr4::class,
            CheckImports::class,
            CheckAll::class,
            ClassifyStrings::class,
        ]);
    }

    public function register()
    {
        if (! $this->canRun()) {
            return;
        }

//        $this->loadConfig();

        // we spy the router in order to have a list of route files.
        $this->spyRouter();

        app()->singleton(ErrorPrinter::class);

        // we need to start spying before the boot process starts.

        $command = $_SERVER['argv'][1] ?? null;

        ($command == 'check:events') && $this->spyEvents();

        ($command == 'check:gates') && $this->spyGates();
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
            return new CheckerGate($app, function () use ($app) {
                return call_user_func($app['auth']->userResolver());
            });
        });
    }

    private function spyEvents()
    {
        $this->app->singleton('events', function ($app) {
            return (new CheckerDispatcher($app))->setQueueResolver(function () use ($app) {
                return $app->make(QueueFactoryContract::class);
            });
        });
        Event::clearResolvedInstance('events');
    }

    private function loadConfig()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-microscope');
    }

    private function canRun()
    {
        return $this->app->runningInConsole() && ! app()->isProduction();
    }
}
