<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Iterators;

class ForAutoloadedPsr4Classes
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\CheckSet  $checkSet
     * @return array<string, \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\Psr4StatsDTO>
     */
    public static function check(CheckSet $checkSet)
    {
        return ChecksOnPsr4Classes::apply($checkSet);
    }
}
