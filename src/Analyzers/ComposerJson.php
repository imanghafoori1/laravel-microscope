<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Composer\ClassMapGenerator\ClassMapGenerator;
use ImanGhafoori\ComposerJson\ComposerJson as Composer;
use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;

class ComposerJson
{
    public static $composer;

    /**
     * @return Composer
     */
    public static function make()
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
     * @return array<string, string[]>
     */
    public static function autoloadedFilesList()
    {
        return self::make()->autoloadedFilesList(BasePath::$path);
    }

    /**
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return array<string, \Generator<string, string[]>>
     */
    public static function getClassMaps($pathDTO)
    {
        $classmaps = [];

        foreach (self::make()->readAutoloadClassMap() as $composerPath => $classMapPaths) {
            $classmaps[$composerPath] = self::getFilteredClasses($composerPath, $classMapPaths, $pathDTO);
        }

        return $classmaps;
    }

    /**
     * @param  string  $composerPath
     * @param  string[]  $classMapPaths
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return \Generator<string, string[]>
     */
    private static function getFilteredClasses($composerPath, $classMapPaths, $pathDTO)
    {
        foreach ($classMapPaths as $classmapPath) {
            $classes = self::getClasses($composerPath, $classmapPath);
            yield $classmapPath => self::filterClasses($classes, $pathDTO);
        }
    }

    /**
     * @param  string  $compPath
     * @param  string  $classmapPath
     * @return string[]
     */
    private static function getClasses($compPath, $classmapPath)
    {
        $compPath1 = trim($compPath, '/');
        $compPath1 = $compPath1 ? $compPath1.DIRECTORY_SEPARATOR : '';
        $classmapFullPath = BasePath::$path.DIRECTORY_SEPARATOR.$compPath1.$classmapPath;

        return array_values(ClassMapGenerator::createMap($classmapFullPath));
    }

    /**
     * @param  string[]  $classes
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return string[]
     */
    private static function filterClasses(array $classes, $pathDTO)
    {
        foreach ($classes as $i => $class) {
            if (! FilePath::contains(str_replace(BasePath::$path, '', $class), $pathDTO)) {
                unset($classes[$i]);
            }
        }

        return $classes;
    }
}
