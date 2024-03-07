<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEnvCalls;

use Illuminate\Support\Facades\Event;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorTypes\MicroEvent;

class EnvFound
{
    use MicroEvent;

    public static function listen()
    {
        Event::listen(self::class, function (EnvFound $event) {
            $data = $event->data;
            ErrorPrinter::singleton()->simplePendError(
                $data['name'],
                $data['absPath'],
                $data['lineNumber'],
                'envFound',
                'env() function found: '
            );
        });
    }
}
