<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\FileReaders\PhpFinder;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;

class FilePathsForReferenceFix
{
    public static $pathsForReferenceFix = [];

    public static function getFiles()
    {
        if (self::$pathsForReferenceFix) {
            // Used for testing and memoization:
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
        // @todo: this should get refactored into the ComposerJson class using the iterator pattern.
        foreach (ComposerJson::readPsr4() as $autoload) {
            foreach ($autoload as $psr4Path) {
                foreach ((array) $psr4Path as $path) {
                    foreach (PhpFinder::getAllPhpFiles($path) as $file) {
                        yield $file->getRealPath();
                    }
                }
            }
        }
    }
}
