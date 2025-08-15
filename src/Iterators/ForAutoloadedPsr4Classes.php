<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Handlers\ErrorExceptionHandler;

class ForAutoloadedPsr4Classes
{
    /**
     * @param  array<class-string<\Imanghafoori\LaravelMicroscope\Iterators\Check>>  $checks
     * @param  array  $params
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return array<string, \Generator<string, \Generator<string, int>>>
     */
    public static function check($checks, $params, $pathDTO)
    {
        ChecksOnPsr4Classes::$errorExceptionHandler = ErrorExceptionHandler::class;

        $checker = CheckSingleMapping::init($checks, $params, $pathDTO);

        return ChecksOnPsr4Classes::apply($checker);
    }

    /**
     * @return void
     */
    public static function checkNow($checks, $params = [], $pathDTO = null, $callback = null)
    {
        self::applyOnStats(
            self::check($checks, $params, $pathDTO),
            $callback
        );
    }

    /**
     * @param  array<string, \Generator<string, \Generator<string, int>>>  $allStats
     * @param  \Closure|null  $callback
     * @return void
     */
    public static function applyOnStats(array $allStats, $callback = null)
    {
        foreach ($allStats as $path => $results) {
            foreach (iterator_to_array($results) as $namespace => $result) {
                foreach ($result as $folder => $count) {
                    $callback && $callback($folder, $count, $path, $namespace);
                }
            }
        }
    }
}
