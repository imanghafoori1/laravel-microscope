<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

abstract class Check
{
    public abstract static function check($tokens, $absFilePath, $processedParams, $phpFilePath, $psr4Path, $psr4Namespace);
}