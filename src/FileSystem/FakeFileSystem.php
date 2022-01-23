<?php

namespace Imanghafoori\LaravelMicroscope\FileSystem;

class FakeFileSystem
{
    public static $absPath;

    public static $newVersion;

    private static $files = [];

    private static $pointers = [];

    public static function read_file($absPath)
    {
        return self::$files[$absPath];
    }

    public static function file_put_contents($absPath, $newVersion)
    {
        self::$absPath = $absPath;
        self::$newVersion = $newVersion;
    }

    public static function feof($stream)
    {
        $i = self::$pointers[$stream];

        return isset(self::$files[$stream][$i]);

        return (bool) current($stream);
        if (! is_string($stream)) {
            dd($stream);
        }

        return (bool) current(self::$files[$stream]);
    }

    public static function fopen($filename, $mode)
    {
        try {
            $lines = file($filename);
        } catch (\ErrorException $e) {
            $lines = [];
        }

        self::$files[$filename] = $lines;
        self::$pointers[$filename] = 0;

        return $filename;
    }

    public static function fgets($stream)
    {
        $i = self::$pointers[$stream];
        $val = (self::$files[$stream][$i]).PHP_EOL;
        self::$pointers[$stream]++;

        return $val;
    }

    public static function fwrite($stream, $data)
    {
        dd($stream);

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

    public static function fclose($filename)
    {
//        unset(self::$files[$filename]);
    }
}
