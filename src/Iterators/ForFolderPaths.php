<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

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
            $paths = array_map(fn ($files) => FilePath::filter($files, $pathDTO), $paths);
        }

        return self::applyOnFiles($paths, $checks, $paramProvider);
    }

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Check[]  $checks
     * @param  array<string, \Generator<int, string>>  $dirsList
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
     * @param $paths
     * @param  array  $checks
     * @param  array|\Closure  $paramProvider
     * @return array<string, \Generator<int, PhpFileDescriptor>>
     */
    private static function applyOnFiles($paths, array $checks, $paramProvider)
    {
        $files = [];
        foreach ($paths as $dir => $absFilePaths) {
            is_string($absFilePaths) && ($absFilePaths = [$absFilePaths]);

            $files[$dir] = self::applyChecks($absFilePaths, $checks, $paramProvider);
        }

        return $files;
    }
}
