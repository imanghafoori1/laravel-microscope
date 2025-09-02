<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\DTO;

class BladeStatDto
{
    /**
     * @var \Generator<string, int>
     */
    public $stats;

    /**
     * @param  \Generator<int, string>  $stats
     * @return self
     */
    public static function make($stats)
    {
        $obj = new self();

        $obj->stats = $stats;

        return $obj;
    }
}