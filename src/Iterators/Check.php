<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

abstract class Check
{
    abstract public static function check(PhpFileDescriptor $file, $processedParams = []);
}
