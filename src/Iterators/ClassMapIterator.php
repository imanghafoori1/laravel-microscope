<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;

class ClassMapIterator extends BaseIterator
{
    /**
     * @param  string  $basePath
     * @param  array  $checks
     * @param  \Closure|null  $paramProvider
     * @param  PathFilterDTO  $pathDTO
     * @return array<string, \Generator<string, \Generator<int, PhpFileDescriptor>>>
     */
    public static function iterate($basePath, $checks, $paramProvider = null, PathFilterDTO $pathDTO)
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
     * @return \Generator<string, \Generator<int, PhpFileDescriptor>>
     */
    private static function getDirStats($classMap, $checks, $paramProvider)
    {
        foreach ($classMap as $dir => $absFilePaths) {
            yield $dir => self::applyChecks($absFilePaths, $checks, $paramProvider);
        }
    }
}
