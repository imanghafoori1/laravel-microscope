<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;

class ForAutoloadedClassMaps extends BaseIterator
{
    /**
     * @param  string  $basePath
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\CheckCollection  $checks
     * @param  PathFilterDTO  $pathDTO
     * @param  array  $params
     * @return array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto>
     */
    public static function check($basePath, $checks, PathFilterDTO $pathDTO, $params = [])
    {
        return Loop::map(
            ComposerJson::getClassMaps($basePath, $pathDTO),
            fn ($classMap) => self::getDirStats($classMap, $checks, $params)
        );
    }

    /**
     * @param  \Generator<string, string[]>  $classMap
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\CheckCollection  $checks
     * @param  array  $params
     * @return \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto
     */
    private static function getDirStats($classMap, $checks, $params)
    {
        return StatsDto::make(Loop::map(
            $classMap,
            fn ($absFilePaths) => self::applyChecks($absFilePaths, $checks, $params)
        ));
    }
}
