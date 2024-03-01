<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\FileReaders\PhpFinder;
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

        // $dirs = [
        //     LaravelPaths::migrationDirs(),
        //     LaravelPaths::configDirs(),
        // ];
        // $paths['others'] = self::collectFilesInNonPsr4Paths($dirs);

        self::$pathsForReferenceFix = $paths;

        return $paths;
    }

    private static function collectFilesInNonPsr4Paths($dirs)
    {
        foreach ($dirs as $dir) {
            yield from Paths::getAbsFilePaths($dir);
        }
    }

    private static function getPsr4()
    {
        foreach (ComposerJson::readPsr4() as $autoload) {
            foreach ($autoload as $psr4Path) {
                foreach (PhpFinder::getAllPhpFiles($psr4Path) as $file) {
                    yield $file->getRealPath();
                }
            }
        }
    }
}
