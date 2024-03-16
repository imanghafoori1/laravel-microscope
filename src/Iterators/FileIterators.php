<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Generator;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;

class FileIterators extends BaseIterator
{
    /**
     * @param  \Generator  $paths
     * @param  \Closure  $paramProvider
     * @param  array  $checks
     * @return \Generator
     */
    public static function checkFilePaths($paths, $paramProvider, $checks)
    {
        foreach ($paths as $dir => $absFilePaths) {
            is_string($absFilePaths) && ($absFilePaths = [$absFilePaths]);

            yield $dir => self::checkFiles($absFilePaths, $paramProvider, $checks);
        }
    }

    /**
     * @param  array<string, \Generator>  $dirsList
     * @param  $paramProvider
     * @param  string  $file
     * @param  string  $folder
     * @param  array  $checks
     * @return \Generator
     */
    public static function checkFolders($checks, $dirsList, $paramProvider, $file, $folder)
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
