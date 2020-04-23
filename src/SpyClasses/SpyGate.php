<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use Illuminate\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class SpyGate extends Gate
{
    public function define($ability, $callback)
    {
        if (! is_string($callback)) {
            return;
        }

        [$class, $method] = Str::parseCallback($callback, '__invoke');

        try {
            $policy = app()->make($class);
        } catch (\Exception $e) {
            return app(ErrorPrinter::class)->print("The $callback callback for Gate, does not refer to a resolvable class, for ability: $ability");
        }

        if (! method_exists($policy, $method)) {
            return app(ErrorPrinter::class)->print("The $callback callback for Gate, does not refer to a valid method, for ability: $ability");
        }
    }

    public function policy($model, $policy)
    {
        if (! is_subclass_of($model, Model::class)) {
            return app(ErrorPrinter::class)->print("The \"$model\" you are trying to define policy for, is not an eloquent model class.");
        }
    }
}
