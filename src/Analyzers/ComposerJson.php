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

    public static function readPsr4($purgeAutoload = false)
    {
        return self::make()->readAutoload($purgeAutoload);
    }

    public static function autoloadedFilesList($basePath)
    {
        return self::make()->autoloadedFilesList($basePath);
    }

    public static function getClassMaps($basePath, $pathDTO)
    {
        foreach (self::make()->readAutoloadClassMap() as $composerPath => $classMapPaths) {
            yield $composerPath => self::getFilteredClasses($composerPath, $classMapPaths, $basePath, $pathDTO);
        }
    }

    private static function getFilteredClasses($composerPath, $classMapPaths, $basePath, $pathDTO)
    {
        foreach ($classMapPaths as $classmapPath) {
            $classes = self::getClasses($composerPath, $basePath, $classmapPath);
            yield $classmapPath => self::filterClasses($classes, $basePath, $pathDTO);
        }
    }

    private static function getClasses($compPath, $basePath, $classmapPath)
    {
        $compPath1 = trim($compPath, '/');
        $compPath1 = $compPath1 ? $compPath1.DIRECTORY_SEPARATOR : '';
        $classmapFullPath = $basePath.DIRECTORY_SEPARATOR.$compPath1.$classmapPath;

        return array_values(ClassMapGenerator::createMap($classmapFullPath));
    }

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
