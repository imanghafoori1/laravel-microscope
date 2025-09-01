<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\DTO;

class Psr4StatsDTO
{
    /**
     * @var array<string, array<string, (callable(): int)>>
     */
    public $stats = [];

    /**
     * @param  array<string, array<string, (callable(): int)>>  $stats
     * @return self
     */
    public static function make($stats)
    {
        $obj = new self();

        $obj->stats = $stats;

        return $obj;
    }
}