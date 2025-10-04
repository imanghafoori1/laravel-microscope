<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\DTO;

class Psr4StatsDTO
{
    /**
     * @var array<string, array<string, (callable(): int)>>
     */
    public $stats = [];

    public static function make($stats)
    {
        return MakeDto::make($stats, self::class);
    }
}
