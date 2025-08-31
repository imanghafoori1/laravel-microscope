<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;

class ForFolderPaths extends BaseIterator
{
    /**
     * @param  array<string, string[]>  $paths
     * @param  array<int, class-string<\Imanghafoori\LaravelMicroscope\Check>>  $checks
     * @param  array|\Closure  $paramProvider
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return array<string, \Generator<int, PhpFileDescriptor>>
     */
    public static function checkFilePaths($paths, $checks, $paramProvider, $pathDTO = null)
    {
        if ($pathDTO) {
            $paths = Loop::map($paths, fn ($files) => FilePath::filter($files, $pathDTO));
        }

        return self::applyOnFiles($paths, $checks, $paramProvider);
    }

    /**
     * @param  array<int, class-string<\Imanghafoori\LaravelMicroscope\Check>>  $checks
     * @param  array<string, \Generator<int, string>>  $dirsList
     * @param  array|\Closure  $paramProvider
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathFilter
     * @return array<string, array<string, \Generator<int, PhpFileDescriptor>>>
     */
    public static function check($checks, $dirsList, $paramProvider, PathFilterDTO $pathFilter)
    {
        return Loop::map($dirsList, fn ($dirs, $listName) => self::checkFilePaths(
            Paths::getAbsFilePaths($dirs, $pathFilter), $checks, $paramProvider
        ));
    }

    /**
     * @param  $paths
     * @param  array<int, class-string<\Imanghafoori\LaravelMicroscope\Check>>  $checks
     * @param  array|\Closure  $paramProvider
     * @return array<string, \Generator<int, PhpFileDescriptor>>
     */
    private static function applyOnFiles($paths, array $checks, $paramProvider)
    {
        return Loop::map(
            $paths,
            fn ($absPaths) => self::applyChecks($absPaths, $checks, $paramProvider)
        );
    }
}
