<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\DTO;

class StatsDto
{
    /**
     * @var array<string, \Generator<int, \Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor>>
     */
    public $stats = [];

    public static function make($stats)
    {
        return MakeDto::make($stats, self::class);
    }
}
