<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Generator;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;

class ForFolderPaths extends BaseIterator
{
    /**
     * @param  \Generator|array  $paths
     * @param  \Imanghafoori\LaravelMicroscope\Check[]  $checks
     * @param  array|\Closure  $paramProvider
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
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
     * @param  \Imanghafoori\LaravelMicroscope\Check[]  $checks
     * @param  array<string, \Generator>  $dirsList
     * @param  array|\Closure  $paramProvider
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathFilter
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
     * @param  \Imanghafoori\LaravelMicroscope\Check[]  $checks
     * @param  array|\Closure  $paramProvider
     * @return \Generator<int, PhpFileDescriptor>
     */
    public static function checkFiles($absFilePaths, $checks, $paramProvider): Generator
    {
        return self::applyChecks($absFilePaths, $checks, $paramProvider);
    }
}
