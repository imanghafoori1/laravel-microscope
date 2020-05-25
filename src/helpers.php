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
        is_array($route) && ($route = implode('@', $route));
        config()->push('microscope.pp.routes', $route);
    }
}
