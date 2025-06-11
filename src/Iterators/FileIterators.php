<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Generator;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;

class FileIterators extends BaseIterator
{
    /**
     * @param  \Generator  $paths
     * @param  \Closure  $paramProvider
     * @param  array  $checks
     * @return \Generator
     */
    public static function checkFilePaths($paths, $checks, $paramProvider)
    {
        foreach ($paths as $dir => $absFilePaths) {
            is_string($absFilePaths) && ($absFilePaths = [$absFilePaths]);

            yield $dir => self::checkFiles($absFilePaths, $checks, $paramProvider);
        }
    }

    /**
     * @param  array  $checks
     * @param  array<string, \Generator>  $dirsList
     * @param  $paramProvider
     * @return \Generator
     */
    public static function checkFolders($checks, $dirsList, $paramProvider, PathFilterDTO $pathFilter)
    {
        foreach ($dirsList as $listName => $dirs) {
            $filePathsGen = Paths::getAbsFilePaths($dirs, $pathFilter);
            yield $listName => self::checkFilePaths($filePathsGen, $checks, $paramProvider);
        }
    }

    /**
     * @param  $absFilePaths
     * @param  $paramProvider
     * @param  $checks
     * @return \Generator
     */
    public static function checkFiles($absFilePaths, $checks, $paramProvider): Generator
    {
        is_string($absFilePaths) && ($absFilePaths = [$absFilePaths]);

        yield from self::applyChecks($absFilePaths, $checks, $paramProvider);
    }
}
