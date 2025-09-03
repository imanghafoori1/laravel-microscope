<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

abstract class BaseIterator
{
    /**
     * @param  \Generator<int, string>|string[]  $absFilePaths
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\CheckCollection  $checks
     * @param  array  $params
     * @return \Generator<int, PhpFileDescriptor>
     */
    public static function applyChecks($absFilePaths, $checks, $params)
    {
        foreach ($absFilePaths as $absFilePath) {
            $fileDescriptor = PhpFileDescriptor::make($absFilePath);
            $checks->applyOnFile($fileDescriptor, $params);

            yield $fileDescriptor;
        }
    }
}
