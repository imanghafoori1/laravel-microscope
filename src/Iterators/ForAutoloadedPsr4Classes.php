<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Handlers\ErrorExceptionHandler;

class ForAutoloadedPsr4Classes
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\CheckSet  $checkSet
     * @return array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\Psr4StatsDTO>
     */
    public static function check(CheckSet $checkSet)
    {
        ChecksOnPsr4Classes::$errorExceptionHandler = ErrorExceptionHandler::class;

        return ChecksOnPsr4Classes::apply($checkSet);
    }
}
