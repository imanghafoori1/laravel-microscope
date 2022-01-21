<?php

namespace Imanghafoori\LaravelMicroscope\FileSystem;

class RealFileSystem
{
    public static function file_put_contents($absPath, $newVersion)
    {
        file_put_contents($absPath, $newVersion);
    }

    public static function feof($stream)
    {
        return feof($stream);
    }

    public static function fopen($filename, $mode)
    {
        return fopen($filename, $mode);
    }

    public static function fgets($stream)
    {
        return fgets($stream);
    }

    public static function fwrite($stream, $data)
    {
        return fwrite($stream, $data);
    }

    public static function rename($from, $to)
    {
        return rename($from, $to);
    }

    public static function unlink($filename)
    {
        return unlink($filename);
    }

    public static function close($filename)
    {
        return fclose($filename);
    }
}
