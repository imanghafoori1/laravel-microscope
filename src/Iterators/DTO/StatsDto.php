<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\DTO;

class StatsDto
{
    /**
     * @var array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\FilesDto>
     */
    public $stats = [];

    public static function make($stats)
    {
        return MakeDto::make($stats, self::class);
    }
}
