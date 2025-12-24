<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Iterators;

use Imanghafoori\LaravelMicroscope\Foundations\Handlers\ErrorExceptionHandler;

class ForAutoloadedPsr4Classes
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\CheckSet  $checkSet
     * @return array<string, \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\Psr4StatsDTO>
     */
    public static function check(CheckSet $checkSet)
    {
        ChecksOnPsr4Classes::$errorExceptionHandler = ErrorExceptionHandler::class;

        return ChecksOnPsr4Classes::apply($checkSet);
    }
}
