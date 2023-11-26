<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckImports\MockExistenceChecker;

class AlwaysExistsMock
{
    public static function check($class, $absFilePath): bool
    {
        return true;
    }
}