<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Iterators;

use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\FilesDto;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;

class ForRouteFiles
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\CheckSet  $checker
     * @return \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\FilesDto
     */
    public static function check(CheckSet $checker)
    {
        $routeFiles = FilePath::filter(RoutePaths::get(), $checker->pathDTO);

        return FilesDto::make(ForFolderPaths::applyChecks($routeFiles, $checker));
    }
}
