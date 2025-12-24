<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Iterators;

use Imanghafoori\LaravelMicroscope\Foundations\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\FilesDto;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\StatsDto;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;

class ForAutoloadedClassMaps extends BaseIterator
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\CheckSet  $checkSet
     * @return array<string, \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\StatsDto>
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
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\CheckSet  $checkSet
     * @return \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\StatsDto
     */
    private static function getDirStats($classMap, $checkSet)
    {
        return StatsDto::make(Loop::map(
            $classMap,
            fn ($absFilePaths) => FilesDto::make(self::applyChecks($absFilePaths, $checkSet))
        ));
    }
}
