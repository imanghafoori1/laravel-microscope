<?php

if (! function_exists('extractBlade')) {
    function extractBlade()
    {
        //
    }
}

if (! function_exists('microscope_pretty_print_route')) {
    function microscope_pretty_print_route($route)
    {
        config()->push('microscope.write.routes', debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0]);
    }
}

if (! function_exists('microscope_write_route')) {
    function microscope_write_route(...$routes)
    {
        config()->push('microscope.write.routes', debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0]);
    }
}

if (! function_exists('microscope_dd_listeners')) {
    function microscope_dd_listeners($event)
    {
        config()->push('microscope.dump.listeners', $event);
        app()->booted(function () {
            $events = config('microscope.dump.listeners');
            foreach ($events as $event) {
                $listernsInfo = Event::getOriginalListeners($event);
                dump(' Event:  '.$event);
                dump(' Listeners: ');
                $sp = '     ';
                foreach ($listernsInfo as $i => $listenerInfo) {
                    dump($sp.($i + 1).' - '.$listenerInfo[0]);
                    $relPath = \Imanghafoori\LaravelMicroscope\Analyzers\FilePath::getRelativePath($listenerInfo[1]['file']);
                    dump($sp.'    at '.$relPath.':'.$listenerInfo[1]['line']);
                }
                dump('------------------------------------------');
            }
            dd();
        });
    }
}
