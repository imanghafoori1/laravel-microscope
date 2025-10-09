<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

abstract class BaseIterator
{
    /**
     * @param  \Generator<int, string>|string[]  $absFilePaths
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\CheckSet  $checker
     * @return \Generator<int, PhpFileDescriptor>
     */
    public static function applyChecks($absFilePaths, $checker)
    {
        foreach ($absFilePaths as $absFilePath) {
            $fileDescriptor = PhpFileDescriptor::make($absFilePath);
            $checker->checks->applyOnFile($fileDescriptor);

            yield $fileDescriptor;
        }
    }
}
