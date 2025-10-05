<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckFacadeDocblocks;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedClassMaps;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedPsr4Classes;

class CheckFacadeDocblocks extends BaseCommand
{
    protected $signature = 'check:facades
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $description = 'Checks facade doc-blocks';

    public $initialMsg = 'Checking Facades...';

    public $checks = [FacadeDocblocks::class];

    public function handleCommand()
    {
        FacadeDocblocks::$onFix = function ($class) {
            $this->line('- Fixed doc-blocks for: "'.$class.'"', 'fg=yellow');
        };

        FacadeDocblocks::$onError = function ($accessor, $file) {
            ErrorPrinter::singleton()->simplePendError('"'.$accessor.'"', $file, 20, 'asd', 'The Facade Accessor Not Found.');
        };

        $checkSet = CheckSet::initParam($this->checks);
        $psr4Stats = ForAutoloadedPsr4Classes::check($checkSet);
        $classMapStats = ForAutoloadedClassMaps::check($checkSet);

        Psr4Report::formatAndPrintAutoload($psr4Stats, $classMapStats, $this->getOutput());
    }
}
