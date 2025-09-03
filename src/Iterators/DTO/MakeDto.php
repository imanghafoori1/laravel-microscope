<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\DTO;

class MakeDto
{
    public static function make($stats, $class)
    {
        $obj = new $class();

        $obj->stats = $stats;

        return $obj;
    }
}
