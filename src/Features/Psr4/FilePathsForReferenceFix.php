<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;

class FilePathsForReferenceFix
{
    private static $pathsForReferenceFix = [];

    public static function getFiles()
    {
        if (self::$pathsForReferenceFix) {
            return self::$pathsForReferenceFix;
        }

        $paths = [];
        $paths['psr4'] = self::getPsr4();
        $paths['autoload_files'] = ComposerJson::readAutoloadFiles(base_path());
        $paths['class_map'] = self::getClassMaps(base_path());
        $paths['routes'] = RoutePaths::get();
        $paths['blades'] = LaravelPaths::bladeFilePaths();

        $dirs = [
            LaravelPaths::migrationDirs(),
            config_path(),
            LaravelPaths::factoryDirs(),
            LaravelPaths::seedersDir(),
        ];
        $paths = self::collectFilesInNonPsr4Paths($paths, $dirs);

        self::$pathsForReferenceFix = $paths;

        return $paths;
    }

    private static function collectFilesInNonPsr4Paths($paths, $dirs)
    {
        foreach ($dirs as $dir) {
            $paths = array_merge(Paths::getAbsFilePaths($dir), $paths);
        }

        return $paths;
    }

    private static function getPsr4()
    {
        $psr4 = [];
        foreach (ComposerJson::readAutoload() as $autoload) {
            foreach ($autoload as $psr4Path) {
                foreach (FilePath::getAllPhpFiles($psr4Path) as $file) {
                    $psr4[] = $file->getRealPath();
                }
            }
        }

        return $psr4;
    }

    public static function getClassMaps($basePath)
    {
        $result = [];
        foreach (ComposerJson::make()->readAutoloadClassMap() as $compPath => $classmaps) {
            foreach ($classmaps as $classmap) {
                $compPath = trim($compPath, '/') ? trim($compPath, '/').DIRECTORY_SEPARATOR : '';
                $classmap = $basePath.DIRECTORY_SEPARATOR.$compPath.$classmap;
                $classmap = array_values(ClassMapGenerator::createMap($classmap));
                $result = array_merge($classmap, $result);
            }
        }

        return $result;
    }
}