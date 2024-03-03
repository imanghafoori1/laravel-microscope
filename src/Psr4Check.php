<?php

namespace Imanghafoori\LaravelMicroscope;

interface Psr4Check
{
    public static function check($tokens, $absFilePath, $params, $classFilePath, $psr4Path, $psr4Namespace);
}