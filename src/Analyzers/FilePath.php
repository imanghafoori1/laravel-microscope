<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class FilePath
{
    public static function normalize($path)
    {
        $dir = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);

        $sections = explode(DIRECTORY_SEPARATOR, $dir);

        $res = [];
        foreach ($sections as $i => $section) {
            if ($section == '..') {
                array_pop($res);
            } else {
                $res[] = $section;
            }
        }

        return implode(DIRECTORY_SEPARATOR, $res);
    }

    public static function getRelativePath($absFilePath)
    {
        return trim(Str::replaceFirst(base_path(), '', $absFilePath), DIRECTORY_SEPARATOR);
    }

    public static function getAllPhpFiles($path)
    {
        return (new Finder)->files()->name('*.php')->in(base_path($path));
    }
}
