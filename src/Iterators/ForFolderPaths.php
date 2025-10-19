<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Iterators\DTO\FilesDto;
use Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto;

class ForFolderPaths extends BaseIterator
{
    /**
     * @param  array<string, string[]>  $paths
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\CheckSet  $checker
     * @return StatsDto
     */
    public static function checkFilePaths($paths, CheckSet $checker)
    {
        if ($checker->pathDTO) {
            $paths = Loop::map($paths, fn ($files) => FilePath::filter($files, $checker->pathDTO));
        }

        return self::applyOnFiles($paths, $checker);
    }

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\CheckSet  $checker
     * @param  array<string, \Generator<int, string>>  $dirsList
     * @return array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto>
     */
    public static function check(CheckSet $checker, $dirsList)
    {
        return Loop::map($dirsList, fn ($dirs, $listName) => self::checkFilePaths(
            Paths::getAbsFilePaths($dirs, $checker->pathDTO), $checker
        ));
    }

    /**
     * @param  $paths
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\CheckSet  $checker
     * @return StatsDto
     */
    private static function applyOnFiles($paths, $checker)
    {
        return StatsDto::make(
            Loop::map(
                $paths,
                fn ($absPaths) => FilesDto::make(self::applyChecks($absPaths, $checker))
            )
        );
    }
}
