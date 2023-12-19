<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4;

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
        $paths['autoload_files'] = ComposerJson::autoloadedFilesList(base_path());
        $paths['class_map'] = ComposerJson::getClassMaps(base_path());
        $paths['routes'] = RoutePaths::get();
        $paths['blades'] = LaravelPaths::allBladeFiles();

        $dirs = [
            LaravelPaths::migrationDirs(),
            LaravelPaths::configDirs(),
            //LaravelPaths::factoryDirs(),
            //LaravelPaths::seedersDir(),
        ];
        $paths = self::collectFilesInNonPsr4Paths($paths, $dirs);

        self::$pathsForReferenceFix = $paths;

        return $paths;
    }

    private static function collectFilesInNonPsr4Paths($paths, $dirs)
    {
        foreach ($dirs as $dir) {
            yield from Paths::getAbsFilePaths($dir);
        }
    }

    private static function getPsr4()
    {
        foreach (ComposerJson::readAutoload() as $autoload) {
            foreach ($autoload as $psr4Path) {
                foreach (FilePath::getAllPhpFiles($psr4Path) as $file) {
                    yield $file->getRealPath();
                }
            }
        }
    }
}
