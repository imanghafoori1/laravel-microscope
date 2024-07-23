<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use JetBrains\PhpStorm\Pure;

class FilePath
{
    /**
     * Normalize file path to standard formal
     * For a path like: "/usr/laravel/app\Http\..\..\database" returns "/usr/laravel/database".
     *
     * @param  string  $path  directory path
     * @return string
     */
    #[Pure]
    public static function normalize($path)
    {
        $dir = str_replace(['\\', '/', '//', '\\\\'], DIRECTORY_SEPARATOR, $path);

        $sections = explode(DIRECTORY_SEPARATOR, $dir);

        $result = [];
        foreach ($sections as $section) {
            if ($section == '..') {
                array_pop($result);
            } else {
                $result[] = $section;
            }
        }

        return implode(DIRECTORY_SEPARATOR, $result);
    }

    /**
     * get relative path. removes base path of laravel installation from an absolute path.
     *
     * @param  string  $absFilePath  Absolute directory path
     * @return string
     */
    #[Pure]
    public static function getRelativePath($absFilePath)
    {
        return trim(str_replace(PhpFinder::$basePath, '', $absFilePath), '/\\');
    }

    /**
     * Get all ".php" files in directory by giving a path.
     *
     * @param  string  $path  Directory path
     * @return \Symfony\Component\Finder\Finder
     */
    #[Pure]
    public static function getAllPhpFiles($path, $basePath = '')
    {
        return PhpFinder::getAllPhpFiles($path, $basePath);
    }

    #[Pure]
    public static function getFolderFile($absFilePath): array
    {
        $segments = explode('/', str_replace('\\', '/', self::getRelativePath($absFilePath)));
        $fileName = array_pop($segments);

        return [$fileName, implode('/', $segments)];
    }

    #[Pure]
    public static function contains($filePath, $folder, $file)
    {
        if (! $file && ! $folder) {
            return true;
        }

        [$fileName, $folderPath] = self::getFolderFile($filePath);

        if ($file) {
            foreach (explode(',', $file) as $_file) {
                if (mb_strpos($fileName, $_file) !== false) {
                    return true;
                }
            }
        }

        if ($folder) {
            foreach (explode(',', $folder) as $_folder) {
                if (mb_strpos($folderPath, $_folder) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  $paths
     * @param  $file
     * @param  $folder
     * @return \Generator
     */
    public static function removeExtraPaths($paths, $folder, $file)
    {
        foreach ($paths as $absFilePath) {
            if (self::contains($absFilePath, $folder, $file)) {
                yield $absFilePath;
            }
        }
    }
}
