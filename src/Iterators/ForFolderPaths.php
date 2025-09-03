<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;

class ForFolderPaths extends BaseIterator
{
    /**
     * @param  array<string, string[]>  $paths
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\CheckCollection  $checks
     * @param  array  $paramProvider
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return StatsDto
     */
    public static function checkFilePaths($paths, $checks, $paramProvider, $pathDTO = null)
    {
        if ($pathDTO) {
            $paths = Loop::map($paths, fn ($files) => FilePath::filter($files, $pathDTO));
        }

        return self::applyOnFiles($paths, $checks, $paramProvider);
    }

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\CheckCollection  $checks
     * @param  array<string, \Generator<int, string>>  $dirsList
     * @param  array  $paramProvider
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathFilter
     * @return array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto>
     */
    public static function check($checks, $dirsList, $paramProvider, PathFilterDTO $pathFilter)
    {
        return Loop::map($dirsList, fn ($dirs, $listName) => self::checkFilePaths(
            Paths::getAbsFilePaths($dirs, $pathFilter), $checks, $paramProvider
        ));
    }

    /**
     * @param  $paths
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\CheckCollection  $checks
     * @param  array  $param
     * @return StatsDto
     */
    private static function applyOnFiles($paths, $checks, $param)
    {
        return StatsDto::make(
            Loop::map(
                $paths,
                fn ($absPaths) => self::applyChecks($absPaths, $checks, $param)
            )
        );
    }
}
