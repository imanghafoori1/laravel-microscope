<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Reports;

use Imanghafoori\LaravelMicroscope\Foundations\Iterators\ForAutoloadedClassMaps;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\ForAutoloadedFiles;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\ForAutoloadedPsr4Classes;

class ForComposerJsonFiles
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\CheckSet  $checkSet
     * @return \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\AutoloadStats
     */
    public static function checkAndPrint($checkSet)
    {
        $psr4Stats = ForAutoloadedPsr4Classes::check($checkSet);
        $classMapStats = ForAutoloadedClassMaps::check($checkSet);
        $autoloadedFilesStats = ForAutoloadedFiles::check($checkSet);

        return ComposerJsonReport::formatAutoloads($psr4Stats, $classMapStats, $autoloadedFilesStats);
    }
}
