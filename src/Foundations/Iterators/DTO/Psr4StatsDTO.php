<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO;

class Psr4StatsDTO
{
    /**
     * @var array<string, array<string, (callable(): int)>>
     */
    public $stats = [];

    /**
     * @return self
     */
    public static function make($stats)
    {
        return MakeDto::make($stats, self::class);
    }
}
