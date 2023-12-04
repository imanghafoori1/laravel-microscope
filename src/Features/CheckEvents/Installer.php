<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEvents;

use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Support\Facades\Event;

class Installer
{
    public static function spyEvents()
    {
        app()->booting(function () {
            app()->singleton('events', function ($app) {
                return (new SpyDispatcher($app))->setQueueResolver(function () use ($app) {
                    return $app->make(QueueFactoryContract::class);
                });
            });
            Event::clearResolvedInstance('events');
        });
    }
}
