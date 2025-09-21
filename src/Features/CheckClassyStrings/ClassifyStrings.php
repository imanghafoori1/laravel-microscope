<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckClassyStrings;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Psr4ReportPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedClassMaps;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedPsr4Classes;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;

class ClassifyStrings extends Command
{
    protected $signature = 'check:stringy_classes
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $description = 'Replaces string references with ::class version of them.';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking stringy classes...');
        app()->singleton('current.command', function () {
            return $this;
        });
        $errorPrinter->printer = $this->output;

        $pathDTO = PathFilterDTO::makeFromOption($this);

        $checkSet = CheckSet::init([CheckStringy::class], $pathDTO);

        $psr4Stats = ForAutoloadedPsr4Classes::check($checkSet);
        $classMapStats = ForAutoloadedClassMaps::check(base_path(), $checkSet);

        $lines = Psr4Report::formatAutoloads($psr4Stats, $classMapStats);
        Psr4ReportPrinter::printAll($lines, $this->getOutput());

        $this->getOutput()->writeln(CheckStringyMsg::finished());

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    public static function classifyString(PathFilterDTO $pathDTO): array
    {
        $checkSet = CheckSet::init([CheckStringy::class], $pathDTO);

        $psr4Stats = ForAutoloadedPsr4Classes::check($checkSet);
        $classMapStats = ForAutoloadedClassMaps::check(base_path(), $checkSet);

        return [$psr4Stats, $classMapStats];
    }
}
