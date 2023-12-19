<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

class ClassMapIterator
{
    public static function iterate($classMapFiles, $paramProvider, $checks)
    {
        $stats = [];
        foreach ($classMapFiles as $composerPath => $classMap) {
            foreach ($classMap as $dir => $files) {
                $stats[$composerPath][$dir] = count($files);
                self::applyChecks($files, $checks, $paramProvider);
            }
        }

        return $stats;
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
}
