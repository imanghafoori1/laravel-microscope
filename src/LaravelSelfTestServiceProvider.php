<?php

namespace Imanghafoori\LaravelSelfTest;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Imanghafoori\LaravelSelfTest\Commands\CheckAuth;
use Imanghafoori\LaravelSelfTest\Commands\CheckGate;
use Imanghafoori\LaravelSelfTest\Commands\CheckEvent;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;

class LaravelSelfTestServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([CheckEvent::class, CheckGate::class, CheckAuth::class]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        app()->singleton(ErrorPrinter::class);

        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-self-test');
        if (! $this->app->runningInConsole()) {
            return ;
        }
        $command = $_SERVER['argv'][1] ?? null;

        if ($command == 'check:event') {
            $this->app->singleton('events', function ($app) {
                return (new CheckerDispatcher($app))->setQueueResolver(function () use ($app) {
                    return $app->make(QueueFactoryContract::class);
                });
            });
            Event::clearResolvedInstance('events');
        }

        if ($command == 'check:gate') {
            $this->app->singleton(GateContract::class, function ($app) {
                return new CheckerGate($app, function () use ($app) {
                    return call_user_func($app['auth']->userResolver());
                });
            });
        }
    }
}
