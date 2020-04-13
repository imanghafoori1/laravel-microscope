<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Imanghafoori\LaravelMicroscope\Commands\CheckAll;
use Imanghafoori\LaravelMicroscope\Commands\CheckEvents;
use Imanghafoori\LaravelMicroscope\Commands\CheckGates;
use Imanghafoori\LaravelMicroscope\Commands\CheckImports;
use Imanghafoori\LaravelMicroscope\Commands\CheckPsr4;
use Imanghafoori\LaravelMicroscope\Commands\CheckRoutes;
use Imanghafoori\LaravelMicroscope\Commands\CheckViews;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class LaravelMicroscopeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (! $this->app->runningInConsole() || app()->isProduction()) {
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
        ]);
    }

    public function register()
    {
        if (! $this->app->runningInConsole() || app()->isProduction()) {
            return;
        }

        app()->singleton(ErrorPrinter::class);

//        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-microscope');
        $command = $_SERVER['argv'][1] ?? null;

        if ($command == 'check:events') {
            $this->app->singleton('events', function ($app) {
                return (new CheckerDispatcher($app))->setQueueResolver(function () use ($app) {
                    return $app->make(QueueFactoryContract::class);
                });
            });
            Event::clearResolvedInstance('events');
        }

        if ($command == 'check:gates') {
            $this->app->singleton(GateContract::class, function ($app) {
                return new CheckerGate($app, function () use ($app) {
                    return call_user_func($app['auth']->userResolver());
                });
            });
        }
    }
}
