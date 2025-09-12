<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Iterators\DTO\FilesDto;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;

class ForRouteFiles
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\CheckCollection  $checks
     * @param  array  $params
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return \Imanghafoori\LaravelMicroscope\Iterators\DTO\FilesDto
     */
    public static function check($checks, $pathDTO, $params = [])
    {
        $routeFiles = FilePath::filter(RoutePaths::get(), $pathDTO);

        return FilesDto::make(ForFolderPaths::applyChecks($routeFiles, $checks, $params));
    }
}
