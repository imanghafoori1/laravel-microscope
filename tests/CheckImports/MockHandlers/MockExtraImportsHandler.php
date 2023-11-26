<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckImports\MockHandlers;

class MockExtraImportsHandler
{
    public static $calls = [];

    public static function handle($unusedCorrectImports, $absFilePath)
    {
        self::$calls[] = [$unusedCorrectImports, $absFilePath];
    }

    public static function reset()
    {
        self::$calls = [];
    }
}