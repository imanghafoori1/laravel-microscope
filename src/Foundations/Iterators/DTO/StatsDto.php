<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO;

class StatsDto
{
    /**
     * @var array<string, \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\FilesDto>
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
