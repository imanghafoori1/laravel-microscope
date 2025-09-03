<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\DTO;

class AutoloadStats
{
    /**
     * @var array<int, array<int, string|\Generator<int, string>>>
     */
    public $stats;

    public static function make($stats)
    {
        return MakeDto::make($stats, self::class);
    }

    public function add($msg)
    {
        $this->stats[] = $msg;
    }
}
