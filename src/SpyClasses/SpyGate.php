<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use Illuminate\Support\Str;
use Illuminate\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Container\BindingResolutionException;
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
        } catch (BindingResolutionException $e) {
            return app(ErrorPrinter::class)->print("The $callback callback for Gate, does not refer to a resolvable class, for ability: $ability");
        }

        if (! method_exists($policy, $method)) {
            return app(ErrorPrinter::class)->print("The $callback callback for Gate, does not refer to a valid method, for ability: $ability");
        }
    }

    public function policy($model, $policy)
    {
        if (! class_exists($model)) {
            return app(ErrorPrinter::class)->print("The \"$model\" you are trying to define policy for, does not exist as a valid eloquent class.");
        }

        if (! is_subclass_of($model, Model::class)) {
            return app(ErrorPrinter::class)->print("The \"$model\" you are trying to define policy for, is not an eloquent model class.");
        }

        if (! class_exists($policy)) {
            return app(ErrorPrinter::class)->print("The \"$policy\" is not a valid class to be used as policy for $model.");
        }
    }
}
