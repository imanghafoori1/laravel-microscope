<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

class ClassMapIterator
{
    public static function iterate($classMapFiles, $paramProvider, $checks)
    {
        foreach ($classMapFiles as $composerPath => $classMap) {
            yield $composerPath => self::getDirStats($classMap, $checks, $paramProvider);
        }
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
