<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Exception;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\Finder\Finder as SymfonyFinder;

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
        if ($basePath === '') {
            $basePath = self::$basePath;
        }

        $basePath = rtrim($basePath, '/\\');
        $path = ltrim($path, '/\\');
        $path = $basePath.DIRECTORY_SEPARATOR.$path;

        try {
            return SymfonyFinder::create()->files()->name(self::$fileName.'.php')->in($path);
        } catch (Exception $e) {
            return [];
        }
    }
}
