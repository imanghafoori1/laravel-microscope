<?php

namespace Imanghafoori\LaravelMicroscope\Foundations;

class Loop
{
    public static function map($iterable, callable $callback)
    {
        $result = [];
        foreach ($iterable as $key => $value) {
            $result[$key] = $callback($value, $key);
        }

        return $result;
    }

    public static function any($values, $condition) {
        foreach ($values as $value) {
            if ($condition($value)) {
                return true;
            }
        }

        return false;
    }
}
