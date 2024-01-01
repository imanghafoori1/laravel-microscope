<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

abstract class BaseIterator
{
    protected static function applyChecks($absFilePaths, $checks, $paramProvider)
    {
        foreach ($absFilePaths as $absFilePath) {
            $tokens = token_get_all(file_get_contents($absFilePath));
            $params = $paramProvider($tokens);
            foreach ($checks as $check) {
                $check::check($tokens, $absFilePath, $params);
            }
            yield $absFilePath;
        }
    }
}
