<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Iterators\DTO\FilesDto;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;

class ForRouteFiles
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\CheckSet  $checker
     * @return \Imanghafoori\LaravelMicroscope\Iterators\DTO\FilesDto
     */
    public static function check(CheckSet $checker)
    {
        $routeFiles = FilePath::filter(RoutePaths::get(), $checker->pathDTO);

        return FilesDto::make(ForFolderPaths::applyChecks($routeFiles, $checker));
    }
}
