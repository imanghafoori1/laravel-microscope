<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;

class ForRouteFiles
{
    /**
     * @param  array<int, class-string<\Imanghafoori\LaravelMicroscope\Check>>  $checks
     * @param  array|\Closure  $params
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return \Generator<int, \Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor>
     */
    public static function check($checks, $params, $pathDTO)
    {
        $routeFiles = FilePath::filter(RoutePaths::get(), $pathDTO);

        return ForFolderPaths::applyChecks($routeFiles, $checks, $params);
    }
}
