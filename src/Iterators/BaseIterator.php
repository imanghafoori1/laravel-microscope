<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

abstract class BaseIterator
{
    protected static function applyChecks($absFilePaths, $checks, $params)
    {
        foreach ($absFilePaths as $absFilePath) {
            $tokens = token_get_all(file_get_contents($absFilePath));
            foreach ($checks as $check) {
                $check::check(
                    $tokens,
                    $absFilePath,
                    self::processParams($tokens, $absFilePaths, $params)
                );
            }
            yield $absFilePath;
        }
    }

    private static function processParams($tokens, $absFilePaths, $params)
    {
        return (! is_array($params) && is_callable($params)) ? $params($tokens, $absFilePaths) : $params;
    }
}
