<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;

class ClassMapIterator
{
    /**
     * @param  string  $basePath
     * @param  \Closure  $paramProvider
     * @param  array  $checks
     * @param  string  $folder
     * @param  string  $fileName
     * @return array<string, \Generator>
     */
    public static function iterate($basePath, $checks, $paramProvider, $folder, $fileName)
    {
        $classMapFiles = ComposerJson::getClassMaps($basePath, $folder, $fileName);

        $results = [];
        foreach ($classMapFiles as $composerPath => $classMap) {
            $results[$composerPath] = self::getDirStats($classMap, $checks, $paramProvider);
        }

        return $results;
    }

    private static function applyChecks($files, $checks, $paramProvider): void
    {
        foreach ($files as $absFilePath) {
            $tokens = token_get_all(file_get_contents($absFilePath));
            foreach ($checks as $check) {
                $check::check($tokens, $absFilePath, $paramProvider($tokens));
            }
        }
    }

    private static function getDirStats($classMap, $checks, $paramProvider)
    {
        foreach ($classMap as $dir => $files) {
            self::applyChecks($files, $checks, $paramProvider);
            yield $dir => count($files);
        }
    }
}
