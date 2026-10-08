<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Iterators;

use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\FilesDto;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\StatsDto;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;

class ForFilePaths extends BaseIterator
{
    /**
     * @param  array<string, string[]>  $paths
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\CheckSet  $checker
     * @return StatsDto
     */
    public static function check($paths, CheckSet $checker)
    {
        if ($checker->pathDTO) {
            $paths = Loop::map($paths, static fn ($files) => FilePath::filter($files, $checker->pathDTO));
        }

        return self::applyOnFiles($paths, $checker);
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
                static fn ($absPaths) => FilesDto::make(self::applyChecks($absPaths, $checker))
            )
        );
    }
}
