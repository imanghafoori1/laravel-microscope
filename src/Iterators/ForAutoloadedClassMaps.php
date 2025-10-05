<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Iterators\DTO\FilesDto;
use Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto;

class ForAutoloadedClassMaps extends BaseIterator
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\CheckSet  $checkSet
     * @return array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto>
     */
    public static function check(CheckSet $checkSet)
    {
        return Loop::map(
            ComposerJson::getClassMaps($checkSet->pathDTO),
            fn ($classMap) => self::getDirStats($classMap, $checkSet)
        );
    }

    /**
     * @param  \Generator<string, string[]>  $classMap
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\CheckSet  $checker
     * @return \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto
     */
    private static function getDirStats($classMap, $checker)
    {
        return StatsDto::make(Loop::map(
            $classMap,
            fn ($absFilePaths) => FilesDto::make(self::applyChecks($absFilePaths, $checker))
        ));
    }
}
