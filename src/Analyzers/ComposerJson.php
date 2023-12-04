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
                $compPath = trim($compPath, '/');
                $compPath = $compPath ? $compPath.DIRECTORY_SEPARATOR : '';
                $classmap = $basePath.DIRECTORY_SEPARATOR.$compPath.$classmap;
                $classmap = array_values(ClassMapGenerator::createMap($classmap));
                $result = array_merge($classmap, $result);
            }
        }

        return $result;
    }
}
