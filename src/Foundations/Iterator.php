<?php

namespace Imanghafoori\LaravelMicroscope\Foundations;

use Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\LaravelFoldersReport;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Printer;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\ForAutoloadedClassMaps;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\ForAutoloadedPsr4Classes;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\ForBladeFiles;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\ForFolderPaths;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\ForRouteFiles;
use Imanghafoori\LaravelMicroscope\Foundations\Reports\BladeReport;
use Imanghafoori\LaravelMicroscope\Foundations\Reports\ComposerJsonReport;
use Imanghafoori\LaravelMicroscope\Foundations\Reports\ForComposerJsonFiles;
use Imanghafoori\LaravelMicroscope\Foundations\Reports\RouteReport;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;

class Iterator
{
    /**
     * @var \Imanghafoori\LaravelMicroscope\Foundations\Iterators\CheckSet
     */
    public $checkSet;


    private $output;

    public function __construct($checkSet, $output)
    {
        $this->checkSet = $checkSet;
        $this->output = $output;
    }

    /**
     * @return \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\Psr4StatsDTO[]
     */
    public function forPsr4()
    {
        return ForAutoloadedPsr4Classes::check($this->checkSet);
    }

    /**
     * @return array<string, \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\StatsDto>
     */
    public function forClassmaps()
    {
        return ForAutoloadedClassMaps::check($this->checkSet);
    }

    /**
     * @return string
     */
    public function forBladeFiles(): string
    {
        return BladeReport::getBladeStats(ForBladeFiles::check($this->checkSet));
    }

    public function formatPrintPsr4()
    {
        $this->printAll($this->formatAutoloads($this->forPsr4()));
    }

    public function formatPrintPsr4Classmap()
    {
        $this->printAll($this->formatAutoloads($this->forPsr4(), $this->forClassmaps()));
    }

    /**
     * @return \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\AutoloadStats
     */
    public function formatAutoloads($psr4Stats, $classMapStats = [], $filesStat = [])
    {
        return ComposerJsonReport::formatAutoloads($psr4Stats, $classMapStats, $filesStat);
    }

    /**
     * @return \Generator<int, string>
     */
    public function forMigrationsAndConfigs()
    {
        $foldersStats = ForFolderPaths::check($this->checkSet, LaravelPaths::getMigrationConfig());

        return LaravelFoldersReport::formatFoldersStats($foldersStats);
    }

    /**
     * @return array<int, string|string[]>
     */
    public function forRoutes()
    {
        return RouteReport::getStats(ForRouteFiles::check($this->checkSet));
    }

    /**
     * @return \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\AutoloadStats
     */
    public function forComposerLoadedFiles()
    {
        return ForComposerJsonFiles::checkAndPrint($this->checkSet);
    }

    public function formatPrintForComposerLoadedFiles()
    {
        $this->printAll($this->forComposerLoadedFiles());
    }

    public function printAll($messages): void
    {
        Printer::printAll($messages, $this->output);
    }
}
