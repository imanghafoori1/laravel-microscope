<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO;

class BladeStatDto
{
    /**
     * @var \Generator<string, int>
     */
    public $stats;

    /**
     * @return self
     */
    public static function make($stats)
    {
        return MakeDto::make($stats, self::class);
    }
}
