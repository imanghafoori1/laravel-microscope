<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

abstract class BaseIterator
{
    /**
     * @param  \Generator<int, string>|string[]  $absFilePaths
     * @param  array<int, class-string<\Imanghafoori\LaravelMicroscope\Check>>  $checks
     * @param  array|\Closure  $params
     * @return \Generator<int, PhpFileDescriptor>
     */
    public static function applyChecks($absFilePaths, $checks, $params)
    {
        foreach ($absFilePaths as $absFilePath) {
            $fileDescriptor = PhpFileDescriptor::make($absFilePath);
            foreach ($checks as $check) {
                $check::check($fileDescriptor, $params);
            }
            yield $fileDescriptor;
        }
    }
}
