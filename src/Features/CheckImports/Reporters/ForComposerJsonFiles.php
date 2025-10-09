<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedClassMaps;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedFiles;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedPsr4Classes;

class ForComposerJsonFiles
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\CheckSet  $checkSet
     * @return \Imanghafoori\LaravelMicroscope\Iterators\DTO\AutoloadStats
     */
    public static function checkAndPrint($checkSet)
    {
        $psr4Stats = ForAutoloadedPsr4Classes::check($checkSet);
        $classMapStats = ForAutoloadedClassMaps::check($checkSet);
        $autoloadedFilesStats = ForAutoloadedFiles::check($checkSet);

        return Psr4Report::formatAutoloads($psr4Stats, $classMapStats, $autoloadedFilesStats);
    }
}
