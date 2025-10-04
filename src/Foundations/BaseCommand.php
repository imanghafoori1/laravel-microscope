<?php

namespace Imanghafoori\LaravelMicroscope\Foundations;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\LaravelFoldersReport;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Psr4ReportPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\BladeReport;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\ForComposerJsonFiles;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\RouteReport;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedClassMaps;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedPsr4Classes;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use Imanghafoori\LaravelMicroscope\Iterators\ForFolderPaths;
use Imanghafoori\LaravelMicroscope\Iterators\ForRouteFiles;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;

class BaseCommand extends Command
{
    /**
     * @var ErrorPrinter
     */
    public $errorPrinter;

    public $options;

    public $checkSet;

    public $gitConfirm = false;

    public array $params = [];

    /**
     * @var \Imanghafoori\LaravelMicroscope\Foundations\BaseCommand
     */
    public $confirmer;

    public function handle()
    {
        $this->errorPrinter = ErrorPrinter::singleton();
        $this->errorPrinter->printer = $this->getOutput();
        $this->confirmer = $this->options = CheckSet::$options = $this;
        $this->checkSet = $this->getCheckSet();

        event('microscope.start.command');

        if (property_exists($this, 'initialMsg')) {
            $this->info($this->initialMsg);
        }

        $answer = $this->gitConfirm ? $this->gitConfirm() : true;
        if (! $answer) {
            return;
        }

        /*--------------------*/
        $this->handleCommand($this);
        /*--------------------*/
        CachedFiles::writeCacheFiles();

        if (! $this->errorPrinter->hasErrors()) {
            $this->info($this->customMsg ?? '');
        } else {
            $this->errorPrinter->logErrors();
        }

        $this->printTime();

        return $this->exitCode();
    }

    public function exitCode()
    {
        return $this->errorPrinter->hasErrors() ? 1 : 0;
    }

    public function printTime()
    {
        $this->errorPrinter->printTime();
    }

    public function gitConfirm()
    {
        $this->warn('This command is going to make changes to your files!');

        return $this->output->confirm('Do you have committed everything in git?', true);
    }

    protected function forComposerLoadedFiles()
    {
        return ForComposerJsonFiles::checkAndPrint($this->checkSet);
    }

    protected function printAll($messages): void
    {
        Psr4ReportPrinter::printAll($messages, $this->getOutput());
    }

    protected function getCheckSet(): CheckSet
    {
        return CheckSet::initParam($this->checks, $this->params);
    }

    /**
     * @return \Imanghafoori\LaravelMicroscope\Iterators\DTO\Psr4StatsDTO[]
     */
    protected function forPsr4()
    {
        return ForAutoloadedPsr4Classes::check($this->checkSet);
    }

    /**
     * @return array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto>
     */
    protected function forClassmaps()
    {
        return ForAutoloadedClassMaps::check($this->checkSet);
    }

    protected function forBladeFiles(): string
    {
        $bladeStats = ForBladeFiles::check($this->checkSet);

        return BladeReport::getBladeStats($bladeStats);
    }

    protected function formatPrintPsr4()
    {
        $psr4Stats = $this->forPsr4();

        Psr4Report::formatAndPrintAutoload($psr4Stats, [], $this->getOutput());
    }

    protected function forMigrationsAndConfigs()
    {
        $foldersStats = ForFolderPaths::check($this->checkSet, LaravelPaths::getMigrationConfig());

        return LaravelFoldersReport::formatFoldersStats($foldersStats);
    }

    protected function forRoutes()
    {
        return RouteReport::getStats(
            ForRouteFiles::check($this->checkSet)
        );
    }

    public function output($output)
    {
        $this->output = $output;
    }

    public function input($input)
    {
        $this->input = $input;
    }
}
