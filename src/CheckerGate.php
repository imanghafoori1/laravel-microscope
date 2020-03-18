<?php

namespace Imanghafoori\LaravelSelfTest;

use Illuminate\Support\Str;
use Illuminate\Auth\Access\Gate;
use Illuminate\Contracts\Container\BindingResolutionException;

class CheckerGate extends Gate
{
    public function define($ability, $callback)
    {
        if (! is_string($callback)) {
            return ;
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

        return ;
    }
}
