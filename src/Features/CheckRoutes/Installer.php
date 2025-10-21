<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckRoutes;

use Illuminate\Support\Facades\Route;

/**
 * @codeCoverageIgnore
 */
class Installer
{
    public static function spyRouter()
    {
        $router = new SpyRouter(app('events'), app());
        app()->singleton('router', fn ($app) => $router);
        Route::swap($router);
    }
}
