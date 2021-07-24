<?php

namespace Imanghafoori\LaravelMicroscope\FileSystem;

class RealFileSystem
{
    public static function file_put_contents($absPath, $newVersion)
    {
        file_put_contents($absPath, $newVersion);
    }
}
