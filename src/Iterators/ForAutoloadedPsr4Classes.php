<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Handlers\ErrorExceptionHandler;

class ForAutoloadedPsr4Classes
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\CheckCollection  $checks
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @param  array  $params
     * @return array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\Psr4StatsDTO>
     */
    public static function check($checks, $pathDTO, $params = [])
    {
        ChecksOnPsr4Classes::$errorExceptionHandler = ErrorExceptionHandler::class;

        $checker = CheckSingleMapping::init($checks, $params, $pathDTO);

        return ChecksOnPsr4Classes::apply($checker);
    }
}
