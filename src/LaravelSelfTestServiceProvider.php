<?php

namespace Imanghafoori\LaravelSelfTest;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;

class LaravelSelfTestServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([CheckEvent::class]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-self-test');

        if ($this->app->runningInConsole() and ($_SERVER['argv'][1] ?? null) == 'event:check') {
            $this->app->singleton('events', function ($app) {
                return (new CheckerDispatcher($app))->setQueueResolver(function () use ($app) {
                    return $app->make(QueueFactoryContract::class);
                });
            });
            Event::clearResolvedInstance('events');
        }
    }
}
