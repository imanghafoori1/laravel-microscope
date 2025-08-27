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
