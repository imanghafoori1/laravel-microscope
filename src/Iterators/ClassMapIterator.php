<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;

class ClassMapIterator extends BaseIterator
{
    /**
     * @param  string  $basePath
     * @param  array  $checks
     * @param  \Closure| null  $paramProvider
     * @param  string  $folder
     * @param  string  $fileName
     * @return array<string, \Generator>
     */
    public static function iterate($basePath, $checks, $paramProvider = null, $fileName = '', $folder = '')
    {
        $classMapFiles = ComposerJson::getClassMaps($basePath, $fileName, $folder);

        $results = [];
        foreach ($classMapFiles as $composerPath => $classMap) {
            $results[$composerPath] = self::getDirStats($classMap, $checks, $paramProvider);
        }

        return $results;
    }

    private static function getDirStats($classMap, $checks, $paramProvider)
    {
        foreach ($classMap as $dir => $absFilePaths) {
            yield $dir => self::applyChecks($absFilePaths, $checks, $paramProvider);
        }
    }
}
