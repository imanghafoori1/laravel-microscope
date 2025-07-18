<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

abstract class BaseIterator
{
    /**
     * @return \Generator<int, PhpFileDescriptor>
     */
    protected static function applyChecks($absFilePaths, $checks, $params)
    {
        foreach ($absFilePaths as $absFilePath) {
            $fileDescriptor = PhpFileDescriptor::make($absFilePath);
            foreach ($checks as $check) {
                $check::check($fileDescriptor, self::processParams($fileDescriptor, $params));
            }
            yield $fileDescriptor;
        }
    }

    private static function processParams(PhpFileDescriptor $file, $params)
    {
        return (! is_array($params) && is_callable($params)) ? $params($file) : $params;
    }
}
