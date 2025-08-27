<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\ClassRefCorrector;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\PhpFinder;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;

class FilePathsForReferenceFix
{
    public static $pathsForReferenceFix = [];

    /**
     * @return array<string, \Generator<int, string>>
     */
    public static function getFiles()
    {
        if (self::$pathsForReferenceFix) {
            // Used for testing and memoization:
            return self::$pathsForReferenceFix;
        }

        $paths = [];
        $paths['psr4'] = self::getPsr4();
        $paths['autoload_files'] = self::autoloadedFiles();
        $paths['class_map'] = self::getClassMapList();
        $paths['routes'] = RoutePaths::get();
        $paths['blades'] = LaravelPaths::allBladeFiles();

        return $paths;
    }

    /**
     * @return \Generator<int, string>
     */
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

    /**
     * @return \Generator<int, string>
     */
    private static function getClassMapList()
    {
        foreach (ComposerJson::getClassMaps(base_path(), new PathFilterDTO) as $list) {
            foreach ($list as $paths) {
                foreach ($paths as $path) {
                    yield $path;
                }
            }
        }
    }

    /**
     * @return \Generator<int, string>
     */
    private static function autoloadedFiles()
    {
        foreach (ComposerJson::autoloadedFilesList(base_path()) as $files) {
            foreach ($files as $file) {
                yield $file;
            }
        }
    }
}
