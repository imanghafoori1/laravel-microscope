<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDeadControllers\Dependencies;

class RouteChecker
{
    public static function hasRoute($classAtMethod)
    {
        return (bool) app('router')->getRoutes()->getByAction($classAtMethod);
    }
}
