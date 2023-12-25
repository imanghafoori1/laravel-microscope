<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Exception;
use Symfony\Component\Finder\Finder;

class FilePath
{
    public static $basePath = '';

    public static $fileName = '*';

    public static $directory;

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
        return \trim(str_replace(self::$basePath, '', $absFilePath), '/\\');
    }

    /**
     * get all ".php" files in directory by giving a path.
     *
     * @param  string  $path  Directory path
     * @return \Symfony\Component\Finder\Finder
     */
    public static function getAllPhpFiles($path, $basePath = '')
    {
        if ($basePath === '') {
            $basePath = self::$basePath;
        }

        $basePath = rtrim($basePath, '/\\');
        $path = ltrim($path, '/\\');
        $path = $basePath.DIRECTORY_SEPARATOR.$path;

        try {
            $finder = Finder::create()->files()->name(self::$fileName.'.php')->in($path);
            self::$directory && $finder->path(self::$directory);

            return $finder;
        } catch (Exception $e) {
            return [];
        }
    }

    public static function getFolderFile($absFilePath): array
    {
        $segments = explode('/', str_replace('\\', '/', self::getRelativePath($absFilePath)));
        $fileName = array_pop($segments);

        return [$fileName, implode('/', $segments)];
    }

    public static function contains($absFilePath, $excludeFile, $excludeFolder)
    {
        if (! $excludeFile && ! $excludeFolder) {
            return true;
        }

        [$fileName, $folderPath] = self::getFolderFile($absFilePath);

        if ($excludeFile && mb_strpos($fileName, $excludeFile) !== false) {
            return true;
        }

        if ($excludeFolder && mb_strpos($folderPath, $excludeFolder) !== false) {
            return true;
        }

        return false;
    }

    /**
     * @param  $paths
     * @param  $includeFile
     * @param  $includeFolder
     * @return \Generator
     */
    public static function removeExtraPaths($paths, $includeFile, $includeFolder)
    {
        foreach ($paths as $absFilePath) {
            if (self::contains($absFilePath, $includeFile, $includeFolder)) {
                yield $absFilePath;
            }
        }
    }
}
