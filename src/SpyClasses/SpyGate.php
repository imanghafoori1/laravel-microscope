<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use Exception;
use Illuminate\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class SpyGate extends Gate
{
    public static $definedGatesNum = 0;

    public function define($ability, $callback)
    {
        self::$definedGatesNum++;
        if (is_string($callback)) {
            [$class, $method] = Str::parseCallback($callback, '__invoke');

            try {
                $policy = app()->make($class);
            } catch (Exception $e) {
                return $this->pendError($this->getWrongCallbackMessage($callback, $ability));
            }

            if (! method_exists($policy, $method)) {
                return $this->pendError($this->getWrongMethod($callback, $ability));
            }
        }

        $t = $this->abilities[$ability] ?? null;
        if ($t) {
            $callback1 = is_string($callback) ? $callback : 'Closure';
            $callback2 = is_string($t) ? $t : 'Closure';
            $this->pendError($this->getGateOverriddenMsg($ability, $callback1, $callback2));
        }

        parent::define($ability, $callback);
    }

    public function policy($model, $policy)
    {
        if (! is_subclass_of($model, Model::class)) {
            return app(ErrorPrinter::class)->pended[] = ("The \"$model\" you are trying to define policy for, is not an eloquent model class.");
        }
    }

    private function getWrongCallbackMessage($callback, $ability)
    {
        return "The $callback callback for Gate, does not refer to a resolvable class, for ability: $ability";
    }

    private function getWrongMethod($callback, $ability)
    {
        return "The $callback callback for Gate, does not refer to a valid method, for ability: $ability";
    }

    private function pendError($message)
    {
        return app(ErrorPrinter::class)->pended[] = $message;
    }

    private function getGateOverriddenMsg($ability, $callback1, $callback2)
    {
        return "The Gate definition '$ability' is overridden. loser:".$callback1.' Winner: '.$callback2;
    }
}
