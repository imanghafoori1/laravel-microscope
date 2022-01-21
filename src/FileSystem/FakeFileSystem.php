<?php

namespace Imanghafoori\LaravelMicroscope\FileSystem;

class FakeFileSystem
{
    public static $absPath;

    public static $newVersion;

    private static $files;

    public static function file_put_contents($absPath, $newVersion)
    {
        self::$absPath = $absPath;
        self::$newVersion = $newVersion;
    }

    public static function feof($stream)
    {
        return (bool) current(self::$files[$stream]);
    }

    public static function fopen($filename, $mode)
    {
        if (! isset(self::$files[$filename])) {
            return false;
        }

        return $filename;
    }

    public static function fgets($stream)
    {
        return next(self::$files[$stream]).PHP_EOL;
    }

    public static function fwrite($stream, $data)
    {
        return self::$files[$stream][] = $data;
    }

    public static function rename($from, $to)
    {
        self::$files[$to] = self::$files[$from];

        unset(self::$files[$from]);
    }

    public static function unlink($filename)
    {
        unset(self::$files[$filename]);
    }
}
