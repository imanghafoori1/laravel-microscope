<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;

class ForAutoloadedClassMaps extends BaseIterator
{
    /**
     * @param  string  $basePath
     * @param  array<int, class-string>  $checks
     * @param  \Closure|null  $paramProvider
     * @param  PathFilterDTO  $pathDTO
     * @return array<string, array<string, \Generator<int, PhpFileDescriptor>>>
     */
    public static function check($basePath, $checks, $paramProvider, PathFilterDTO $pathDTO)
    {
        return array_map(
            fn ($classMap) => self::getDirStats($classMap, $checks, $paramProvider),
            ComposerJson::getClassMaps($basePath, $pathDTO)
        );
    }

    /**
     * @param  \Generator<string, string>|string[]  $classMap
     * @param  \Imanghafoori\LaravelMicroscope\Check[]  $checks
     * @param  array|\Closure  $paramProvider
     * @return array<string, \Generator<int, PhpFileDescriptor>>
     */
    private static function getDirStats($classMap, $checks, $paramProvider)
    {
        $stats = [];

        foreach ($classMap as $dir => $absFilePaths) {
            $stats[$dir] = self::applyChecks($absFilePaths, $checks, $paramProvider);
        }

        return $stats;
    }
}
