<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\DTO;

use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class StatsDto
{
    /**
     * @var  array<string, \Generator<int, PhpFileDescriptor>>
     */
    public $stats = [];

    /**
     * @param  array<string, \Generator<int, PhpFileDescriptor>>  $stats
     * @return self
     */
    public static function make($stats)
    {
        $obj = new self();

        $obj->stats = $stats;

        return $obj;
    }
}
