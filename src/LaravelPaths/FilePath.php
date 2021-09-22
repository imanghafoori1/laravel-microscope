<?php

namespace Imanghafoori\LaravelMicroscope\LaravelPaths;

use Illuminate\Support\Str;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

class FilePath
{
    /**
     * Normalize file path to standard formal
     * For a path like: "/usr/laravel/app\Http\..\..\database" returns "/usr/laravel/database".
     *
     * @param  string  $path  directory path
     * @return string
     */
    public static function normalize($path)
    {
        $dir = \str_replace(['\\', '/', '//', '\\\\'], DIRECTORY_SEPARATOR, $path);

        $sections = \explode(DIRECTORY_SEPARATOR, $dir);

        $result = [];
        foreach ($sections as $section) {
            if ($section == '..') {
                \array_pop($result);
            } else {
                $result[] = $section;
            }
        }

        return \implode(DIRECTORY_SEPARATOR, $result);
    }

    /**
     * get relative path. removes base path of laravel installation from an absolute path.
     *
     * @param  string  $absFilePath  Absolute directory path
     * @return string
     */
    public static function getRelativePath($absFilePath)
    {
        return \trim(Str::replaceFirst(base_path(), '', $absFilePath), '/\\');
    }

    /**
     * get all ".php" files in directory by giving a path.
     *
     * @param  string  $path  Directory path
     * @return \Symfony\Component\Finder\Finder
     */
    public static function getAllPhpFiles($path, $basePath = '')
    {
        $basePath === null && $path = base_path($path);
        try {
            return Finder::create()->files()->name('*.php')->in($basePath.$path);
        } catch (DirectoryNotFoundException $e) {
            return [];
        }
    }
}
