<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Exception;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\Finder\Finder;

class PhpFinder
{
    public static $basePath = '';

    public static $fileName = '*';

    /**
     * get all ".php" files in directory by giving a path.
     *
     * @param  string  $path  Directory path
     * @return \Symfony\Component\Finder\Finder
     */
    #[Pure]
    public static function getAllPhpFiles($path, $basePath = '')
    {
        $dir = self::getDir($basePath, $path);

        return self::getPathsInDir($dir, self::$fileName);
    }

    /**
     * @param  $dir
     * @param  $fileName
     * @return \Symfony\Component\Finder\Finder|array
     */
    #[Pure]
    public static function getPathsInDir($dir, $fileName)
    {
        try {
            return Finder::create()->files()->name(($fileName ?: '*').'.php')->in($dir);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * @param  string  $basePath
     * @param  string  $path
     * @return string
     */
    #[Pure]
    private static function getDir($basePath, $path)
    {
        if ($basePath === '') {
            $basePath = self::$basePath;
        }

        $basePath = rtrim($basePath, '/\\');
        $path = ltrim($path, '/\\');

        return $basePath.DIRECTORY_SEPARATOR.$path;
    }
}
