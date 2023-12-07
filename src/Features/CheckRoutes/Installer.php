<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckRoutes;

use Illuminate\Support\Facades\Route;

class Installer
{
    public static function spyRouter()
    {
        $router = new SpyRouter(app('events'), app());
        app()->singleton('router', function ($app) use ($router) {
            return $router;
        });
        Route::swap($router);
    }
}
