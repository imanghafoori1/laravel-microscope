<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;

class ForAutoloadedClassMaps extends BaseIterator
{
    /**
     * @param  string  $basePath
     * @param  array  $checks
     * @param  \Closure|null  $paramProvider
     * @param  PathFilterDTO  $pathDTO
     * @return array<string, array<string, \Generator<int, PhpFileDescriptor>>>
     */
    public static function check($basePath, $checks, $paramProvider = null, PathFilterDTO $pathDTO)
    {
        $classMapFiles = ComposerJson::getClassMaps($basePath, $pathDTO);

        $results = [];
        foreach ($classMapFiles as $composerPath => $classMap) {
            $results[$composerPath] = self::getDirStats($classMap, $checks, $paramProvider);
        }

        return $results;
    }

    /**
     * @param  \Generator<string, string>  $classMap
     * @param  $checks
     * @param  $paramProvider
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
