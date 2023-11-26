<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckImports\MockHandlers;

class MockWrongClassRefsHandler
{
    public static $calls = [];

    public static function handle(array $wrongClassRefs, $absFilePath)
    {
        self::$calls[] = [$wrongClassRefs, $absFilePath];
    }

    public static function reset()
    {
        self::$calls = [];
    }
}