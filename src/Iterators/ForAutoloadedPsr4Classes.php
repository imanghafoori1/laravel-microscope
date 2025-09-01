<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Handlers\ErrorExceptionHandler;

class ForAutoloadedPsr4Classes
{
    /**
     * @param  array<int, class-string<\Imanghafoori\LaravelMicroscope\Check>>  $checks
     * @param  array  $params
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\Psr4StatsDTO>
     */
    public static function check($checks, $params, $pathDTO)
    {
        ChecksOnPsr4Classes::$errorExceptionHandler = ErrorExceptionHandler::class;

        $checker = CheckSingleMapping::init($checks, $params, $pathDTO);

        return ChecksOnPsr4Classes::apply($checker);
    }
}
