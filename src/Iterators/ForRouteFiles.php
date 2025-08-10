<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;

class ForRouteFiles
{
    public static function check($checks, $params, $pathDTO)
    {
        $routeFiles = FilePath::removeExtraPaths(RoutePaths::get(), $pathDTO);

        return ForFolderPaths::checkFiles($routeFiles, $checks, $params);
    }
}
