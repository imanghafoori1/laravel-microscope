<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Analyzers;

use Composer\ClassMapGenerator\ClassMapGenerator;
use ImanGhafoori\ComposerJson\ComposerJson as Composer;
use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;

class ComposerJson
{
    /**
     * @var \Closure
     */
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
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\PathFilterDTO  $pathDTO
     * @return array<string, \Generator<string, string[]>>
     */
    public static function getClassMaps($pathDTO)
    {
        return Loop::map(
            self::make()->readAutoloadClassMap(),
            fn ($classMapPaths, $composerPath) => self::getFilteredClasses($composerPath, $classMapPaths, $pathDTO)
        );
    }

    /**
     * @param  string  $composerPath
     * @param  string[]  $classMapPaths
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\PathFilterDTO  $pathDTO
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
        $ds = DIRECTORY_SEPARATOR;
        $compPath1 = trim($compPath, '/');
        $compPath1 = $compPath1 ? $compPath1.$ds : '';
        $classmapFullPath = BasePath::$path.$ds.$compPath1.$classmapPath;

        return array_values(ClassMapGenerator::createMap($classmapFullPath));
    }

    /**
     * @param  string[]  $paths
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\PathFilterDTO  $pathDTO
     * @return string[]
     */
    private static function filterClasses(array $paths, $pathDTO)
    {
        return Loop::mapIf(
            $paths,
            fn ($path) => FilePath::contains(FilePath::getRelativePath($path), $pathDTO),
            fn ($val, $key) => [$key => $val]
        );
    }
}
