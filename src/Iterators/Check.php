<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

abstract class Check
{
    abstract public static function check($tokens, $absFilePath, $processedParams, $phpFilePath, $psr4Path, $psr4Namespace);
}
