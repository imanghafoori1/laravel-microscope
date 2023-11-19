<?php

namespace Imanghafoori\LaravelMicroscope\Features\RouteOverride;

use Illuminate\Support\Facades\Event;

class Installer
{
    public static function install()
    {
        Event::listen(RouteDefinitionConflict::class, function ($e) {
            RouteDefinitionPrinter::routeDefinitionConflict(
                $e->data['poorRoute'],
                $e->data['bullyRoute'],
                $e->data['info']
            );
        });
    }
}
