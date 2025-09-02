<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\DTO;

trait MakeDto
{
    public static function make($stats)
    {
        $obj = new self();

        $obj->stats = $stats;

        return $obj;
    }
}