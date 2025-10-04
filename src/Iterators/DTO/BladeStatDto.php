<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\DTO;

class BladeStatDto
{
    /**
     * @var \Generator<string, int>
     */
    public $stats;

    public static function make($stats)
    {
        return MakeDto::make($stats, self::class);
    }
}
