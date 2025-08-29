<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;

class ForAutoloadedClassMaps extends BaseIterator
{
    /**
     * @param  string  $basePath
     * @param  array<int, class-string<\Imanghafoori\LaravelMicroscope\Check>>  $checks
     * @param  \Closure|null  $paramProvider
     * @param  PathFilterDTO  $pathDTO
     * @return array<string, array<string, \Generator<int, PhpFileDescriptor>>>
     */
    public static function check($basePath, $checks, $paramProvider, PathFilterDTO $pathDTO)
    {
        return Loop::map(
            ComposerJson::getClassMaps($basePath, $pathDTO),
            fn ($classMap) => self::getDirStats($classMap, $checks, $paramProvider)
        );
    }

    /**
     * @param  \Generator<string, string[]>  $classMap
     * @param  array<int, class-string<\Imanghafoori\LaravelMicroscope\Check>>  $checks
     * @param  array|\Closure  $paramProvider
     * @return array<string, \Generator<int, PhpFileDescriptor>>
     */
    private static function getDirStats($classMap, $checks, $paramProvider)
    {
        return Loop::map(
            $classMap,
            fn ($absFilePaths) => self::applyChecks($absFilePaths, $checks, $paramProvider)
        );
    }
}
