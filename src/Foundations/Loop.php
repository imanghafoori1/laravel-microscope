<?php

namespace Imanghafoori\LaravelMicroscope\Foundations;

class Loop
{
    public static function over($iterable, callable $callback)
    {
        foreach ($iterable as $key => $value) {
            $callback($value, $key);
        }
    }

    public static function walkCount($iterable, callable $callback)
    {
        $count = 0;
        foreach ($iterable as $key => $value) {
            $callback($value, $key) && $count++;
        }

        return $count;
    }

    public static function countAll($iterable)
    {
        $count = 0;
        foreach ($iterable as $v) {
            $count++;
        }

        return $count;
    }

    public static function map($iterable, callable $callback)
    {
        $result = [];
        foreach ($iterable as $key => $value) {
            $result[$key] = $callback($value, $key);
        }

        return $result;
    }

    public static function list($iterable, callable $callback)
    {
        $result = [];
        foreach ($iterable as $key => $value) {
            $result[] = $callback($value, $key);
        }

        return $result;
    }

    /**
     * @return bool
     */
    public static function any($values, $condition)
    {
        foreach ($values as $value) {
            if ($condition($value)) {
                return true;
            }
        }

        return false;
    }

    public static function mapKey($iterable, $callback)
    {
        $result = [];
        foreach ($iterable as $key => $value) {
            foreach ($callback($value, $key) as $key => $value) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public static function mapIf($iterable, $if, $callback)
    {
        $result = [];
        foreach ($iterable as $key => $value) {
            if ($if($value, $key)) {
                foreach ($callback($value, $key) as $key => $value) {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    public static function filter($iterable, $callback)
    {
        $items = [];
        foreach ($iterable as $key => $item) {
            if ($callback($item, $key)) {
                $items[$key] = $item;
            }
        }

        return $items;
    }
}
