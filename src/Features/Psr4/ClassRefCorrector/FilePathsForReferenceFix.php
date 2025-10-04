<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\ClassRefCorrector;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\PhpFinder;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;

class FilePathsForReferenceFix
{
    /**
     * @var array<string, \Generator<int, string>>
     */
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

        return [
            'psr4' => self::getPsr4(),
            'autoload_files' => self::autoloadedFiles(),
            'class_map' => self::getClassMapList(),
            'routes' => RoutePaths::get(),
            'blades' => LaravelPaths::allBladeFiles(),
        ];
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
        foreach (ComposerJson::getClassMaps(new PathFilterDTO) as $list) {
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
        foreach (ComposerJson::autoloadedFilesList() as $files) {
            foreach ($files as $file) {
                yield $file;
            }
        }
    }
}
