<?php

namespace Imanghafoori\LaravelMicroscope\FileSystem;

class FakeFileSystem
{
    public static $absPath;

    public static $newVersion;

    public static function file_put_contents($absPath, $newVersion)
    {
        self::$absPath = $absPath;
        self::$newVersion = $newVersion;
    }
}
