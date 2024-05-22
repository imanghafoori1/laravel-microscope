<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

abstract class BaseIterator
{
    protected static function applyChecks($absFilePaths, $checks, $params)
    {
        foreach ($absFilePaths as $absFilePath) {
            $file = PhpFileDescriptor::make($absFilePath);
            foreach ($checks as $check) {
                $check::check($file, self::processParams($file, $params));
            }
            yield $file;
        }
    }

    private static function processParams(PhpFileDescriptor $file, $params)
    {
        return (! is_array($params) && is_callable($params)) ? $params($file) : $params;
    }
}
