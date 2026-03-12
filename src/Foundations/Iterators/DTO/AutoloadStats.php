<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO;

use ArrayIterator;
use IteratorAggregate;

class AutoloadStats implements IteratorAggregate
{
    /**
     * @var array<int, array<int, string|\Generator<int, string>>>
     */
    public $stats;

    /**
     * @return self
     */
    public static function make($stats)
    {
        return MakeDto::make($stats, self::class);
    }

    public function add($msg)
    {
        $this->stats[] = $msg;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->stats);
    }
}
