<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;

class ForRouteFiles
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\CheckCollection  $checks
     * @param  array  $params
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return \Generator<int, \Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor>
     */
    public static function check($checks, $pathDTO, $params = [])
    {
        $routeFiles = FilePath::filter(RoutePaths::get(), $pathDTO);

        return ForFolderPaths::applyChecks($routeFiles, $checks, $params);
    }
}
