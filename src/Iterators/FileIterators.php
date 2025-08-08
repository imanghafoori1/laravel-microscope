<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Generator;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;

class FileIterators extends BaseIterator
{
    /**
     * @param  \Generator|array  $paths
     * @param  \Closure  $paramProvider
     * @param  array  $checks
     * @return array<string, \Generator<int, PhpFileDescriptor>>
     */
    public static function checkFilePaths($paths, $checks, $paramProvider, $pathDTO = null)
    {
        if ($pathDTO) {
            foreach ($paths as $path => $autoloadFile) {
                $paths[$path] = FilePath::removeExtraPaths($autoloadFile, $pathDTO);
            }
        }

        $files = [];
        foreach ($paths as $dir => $absFilePaths) {
            is_string($absFilePaths) && ($absFilePaths = [$absFilePaths]);

            $files[$dir] = self::checkFiles($absFilePaths, $checks, $paramProvider);
        }

        return $files;
    }

    /**
     * @param  array  $checks
     * @param  array<string, \Generator>  $dirsList
     * @param  $paramProvider
     * @return array<string, array<string, \Generator<int, PhpFileDescriptor>>>
     */
    public static function checkFolders($checks, $dirsList, $paramProvider, PathFilterDTO $pathFilter)
    {
        $lists = [];
        foreach ($dirsList as $listName => $dirs) {
            $filePathsGen = Paths::getAbsFilePaths($dirs, $pathFilter);
            $lists[$listName] = self::checkFilePaths($filePathsGen, $checks, $paramProvider);
        }

        return $lists;
    }

    /**
     * @param  $absFilePaths
     * @param  $paramProvider
     * @param  $checks
     * @return \Generator<int, PhpFileDescriptor>
     */
    public static function checkFiles($absFilePaths, $checks, $paramProvider): Generator
    {
        return self::applyChecks($absFilePaths, $checks, $paramProvider);
    }
}
