<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Iterators;

use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\FilesDto;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\StatsDto;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;

class ForFolderPaths extends BaseIterator
{
    /**
     * @param  array<string, string[]>  $paths
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\CheckSet  $checker
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
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\CheckSet  $checker
     * @param  array<string, \Generator<int, string>>  $dirsList
     * @return array<string, \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\StatsDto>
     */
    public static function check(CheckSet $checker, $dirsList)
    {
        return Loop::map($dirsList, fn ($dirs, $listName) => self::checkFilePaths(
            Paths::getAbsFilePaths($dirs, $checker->pathDTO), $checker
        ));
    }

    /**
     * @param  $paths
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\CheckSet  $checker
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
