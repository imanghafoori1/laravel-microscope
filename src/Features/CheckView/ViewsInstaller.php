<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckView;

use Illuminate\Support\Facades\Event;
use Imanghafoori\LaravelMicroscope\Features\CheckView\Check\CheckView;

class ViewsInstaller
{
    public static function boot()
    {
        Event::listen(BladeFile::class, function (BladeFile $event) {
            $data = $event->data;
            CheckView::viewError(
                $data['absPath'],
                'The blade file is missing:',
                $data['lineNumber'],
                $data['name']
            );
        });
    }
}
