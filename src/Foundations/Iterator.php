<?php

namespace Imanghafoori\LaravelMicroscope\Foundations;

use Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\LaravelFoldersReport;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Psr4ReportPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\BladeReport;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\ForComposerJsonFiles;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\RouteReport;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedClassMaps;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedPsr4Classes;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use Imanghafoori\LaravelMicroscope\Iterators\ForFolderPaths;
use Imanghafoori\LaravelMicroscope\Iterators\ForRouteFiles;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;

class Iterator
{
    public $checkSet;

    private $output;

    public function __construct($checkSet, $output)
    {
        $this->checkSet = $checkSet;
        $this->output = $output;
    }

    /**
     * @return \Imanghafoori\LaravelMicroscope\Iterators\DTO\Psr4StatsDTO[]
     */
    public function forPsr4()
    {
        return ForAutoloadedPsr4Classes::check($this->checkSet);
    }

    /**
     * @return array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto>
     */
    public function forClassmaps()
    {
        return ForAutoloadedClassMaps::check($this->checkSet);
    }

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

    public function formatAutoloads($psr4Stats, $classMapStats = [], $filesStat = [])
    {
        return Psr4Report::formatAutoloads($psr4Stats, $classMapStats, $filesStat);
    }

    public function forMigrationsAndConfigs()
    {
        $foldersStats = ForFolderPaths::check($this->checkSet, LaravelPaths::getMigrationConfig());

        return LaravelFoldersReport::formatFoldersStats($foldersStats);
    }

    public function forRoutes()
    {
        return RouteReport::getStats(
            ForRouteFiles::check($this->checkSet)
        );
    }

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
        Psr4ReportPrinter::printAll($messages, $this->output);
    }
}
