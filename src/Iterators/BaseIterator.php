<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

abstract class BaseIterator
{
    /**
     * @param  \Generator<int, string>|string[]  $absFilePaths
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\CheckSet  $checkSet
     * @return \Generator<int, PhpFileDescriptor>
     */
    public static function applyChecks($absFilePaths, $checkSet)
    {
        foreach ($absFilePaths as $absFilePath) {
            $fileDescriptor = PhpFileDescriptor::make($absFilePath);
            $checkSet->checks->applyOnFile($fileDescriptor);

            yield $fileDescriptor;
        }
    }
}
