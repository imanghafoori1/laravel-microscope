<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Generator;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;

class FileIterators extends BaseIterator
{
    /**
     * @param  \Generator  $paths
     * @param  \Closure  $paramProvider
     * @return \Generator
     */
    public static function checkFilePaths($paths, $paramProvider, $checks)
    {
        foreach ($paths as $dir => $absFilePaths) {
            if (is_string($absFilePaths)) {
                $absFilePaths = [$absFilePaths];
            }

            yield $dir => self::checkFiles($absFilePaths, $paramProvider, $checks);
        }
    }

    /**
     * @return array<string, \Generator>
     */
    public static function getLaravelFolders()
    {
        return [
            'config' => LaravelPaths::configDirs(),
            'migrations' => LaravelPaths::migrationDirs(),
        ];
    }

    /**
     * @param  array<string, \Generator>  $dirsList
     * @param  $paramProvider
     * @param  string  $file
     * @param  string  $folder
     * @param  array  $checks
     * @return \Generator
     */
    public static function checkFolders($dirsList, $paramProvider, $file, $folder, $checks)
    {
        foreach ($dirsList as $listName => $dirs) {
            $filePathsGen = Paths::getAbsFilePaths($dirs, $file, $folder);
            yield $listName => self::checkFilePaths($filePathsGen, $paramProvider, $checks);
        }
    }

    /**
     * @param  $absFilePaths
     * @param  $paramProvider
     * @param  $checks
     * @return \Generator
     */
    public static function checkFiles($absFilePaths, $paramProvider, $checks): Generator
    {
        is_string($absFilePaths) && ($absFilePaths = [$absFilePaths]);

        yield from self::applyChecks($absFilePaths, $checks, $paramProvider);
    }
}
