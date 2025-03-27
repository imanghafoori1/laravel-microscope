<?php

namespace Imanghafoori\LaravelMicroscope;

use Imanghafoori\LaravelMicroscope\Handlers\ErrorExceptionHandler;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSingleMapping;
use Imanghafoori\LaravelMicroscope\Iterators\ChecksOnPsr4Classes;

class ForPsr4LoadedClasses
{
    /**
     * @param  array<class-string<\Imanghafoori\LaravelMicroscope\Iterators\Check>>  $checks
     * @param  array  $params
     * @param  string  $includeFile
     * @param  string  $includeFolder
     * @return array<string, \Generator<string, \Generator<string, int>>>
     */
    public static function check($checks, $params = [], $includeFile = '', $includeFolder = '')
    {
        ChecksOnPsr4Classes::$errorExceptionHandler = ErrorExceptionHandler::class;

        $checker = CheckSingleMapping::init($checks, $params, $includeFile, $includeFolder);

        return ChecksOnPsr4Classes::apply($checker);
    }

    /**
     * @return void
     */
    public static function checkNow($checks, $params = [], $includeFile = '', $includeFolder = '', $callback = null)
    {
        self::applyOnStats(
            self::check($checks, $params, $includeFile, $includeFolder),
            $callback
        );
    }

    /**
     * @param array<string, \Generator<string, \Generator<string, int>>> $allStats
     * @param \Closure|null $callback
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
