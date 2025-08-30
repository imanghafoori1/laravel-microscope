<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Composer\ClassMapGenerator\ClassMapGenerator;
use ImanGhafoori\ComposerJson\ComposerJson as Composer;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;

class ComposerJson
{
    public static $composer;

    public static function make(): Composer
    {
        return (self::$composer)();
    }

    /**
     * @param  $purgeAutoload
     * @return array<string, array<string, array>>
     */
    public static function readPsr4($purgeAutoload = false)
    {
        return self::make()->readAutoload($purgeAutoload);
    }

    /**
     * @param  string  $basePath
     * @return array<string, string[]>
     */
    public static function autoloadedFilesList($basePath)
    {
        return self::make()->autoloadedFilesList($basePath);
    }

    /**
     * @param  string  $basePath
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return array<string, \Generator<string, string[]>>
     */
    public static function getClassMaps($basePath, $pathDTO)
    {
        $classmaps = [];

        foreach (self::make()->readAutoloadClassMap() as $composerPath => $classMapPaths) {
            $classmaps[$composerPath] = self::getFilteredClasses($composerPath, $classMapPaths, $basePath, $pathDTO);
        }

        return $classmaps;
    }

    /**
     * @param  string  $composerPath
     * @param  string[]  $classMapPaths
     * @param  string  $basePath
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return \Generator<string, string[]>
     */
    private static function getFilteredClasses($composerPath, $classMapPaths, $basePath, $pathDTO)
    {
        foreach ($classMapPaths as $classmapPath) {
            $classes = self::getClasses($composerPath, $basePath, $classmapPath);
            yield $classmapPath => self::filterClasses($classes, $basePath, $pathDTO);
        }
    }

    /**
     * @param  string  $compPath
     * @param  string  $basePath
     * @param  string  $classmapPath
     * @return string[]
     */
    private static function getClasses($compPath, $basePath, $classmapPath)
    {
        $compPath1 = trim($compPath, '/');
        $compPath1 = $compPath1 ? $compPath1.DIRECTORY_SEPARATOR : '';
        $classmapFullPath = $basePath.DIRECTORY_SEPARATOR.$compPath1.$classmapPath;

        return array_values(ClassMapGenerator::createMap($classmapFullPath));
    }

    /**
     * @param  string[]  $classes
     * @param  string  $basePath
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return string[]
     */
    private static function filterClasses(array $classes, $basePath, $pathDTO)
    {
        foreach ($classes as $i => $class) {
            if (! FilePath::contains(str_replace($basePath, '', $class), $pathDTO)) {
                unset($classes[$i]);
            }
        }

        return $classes;
    }
}
