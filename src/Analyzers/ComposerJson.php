<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Composer\ClassMapGenerator\ClassMapGenerator;
use ImanGhafoori\ComposerJson\ComposerJson as Composer;

class ComposerJson
{
    public static $composer;

    public static function make(): Composer
    {
        return (self::$composer)();
    }

    public static function readAutoload($purgeAutoload = false)
    {
        return self::make()->readAutoload($purgeAutoload);
    }

    public static function autoloadedFilesList($basePath)
    {
        return self::make()->autoloadedFilesList($basePath);
    }

    public static function getClassMaps($basePath)
    {
        $result = [];
        foreach (self::make()->readAutoloadClassMap() as $compPath => $classMaps) {
            foreach ($classMaps as $classmap) {
                $compPath1 = trim($compPath, '/');
                $compPath1 = $compPath1 ? $compPath1.DIRECTORY_SEPARATOR : '';
                $classmapFullPath = $basePath.DIRECTORY_SEPARATOR.$compPath1.$classmap;
                $classes = array_values(ClassMapGenerator::createMap($classmapFullPath));
                $result[$compPath][$classmap] = $classes;
            }
        }

        return $result;
    }
}
