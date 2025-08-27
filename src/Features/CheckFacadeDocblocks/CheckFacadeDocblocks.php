<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckFacadeDocblocks;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedClassMaps;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedPsr4Classes;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckFacadeDocblocks extends Command
{
    use LogsErrors;

    protected $signature = 'check:facades
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $description = 'Checks facade doc-blocks';

    public function handle()
    {
        event('microscope.start.command');
        $this->info('Checking Facades...');

        $errorPrinter = ErrorPrinter::singleton($this->output);

        FacadeDocblocks::$onFix = function ($class) {
            $this->line('- Fixed doc-blocks for: "'.$class.'"', 'fg=yellow');
        };

        FacadeDocblocks::$onError = function ($accessor, $absFilePath) {
            ErrorPrinter::singleton()->simplePendError('"'.$accessor.'"', $absFilePath, 20, 'asd', 'The Facade Accessor Not Found.');
        };

        $pathDTO = PathFilterDTO::makeFromOption($this);

        $check = [FacadeDocblocks::class];
        $psr4Stats = ForAutoloadedPsr4Classes::check($check, [], $pathDTO);
        $classMapStats = ForAutoloadedClassMaps::check(base_path(), $check, null, $pathDTO);

        Psr4Report::formatAndPrintAutoload($psr4Stats, $classMapStats, $this->getOutput());

        $errorPrinter->printTime();
        $errorPrinter->logErrors();

        return $errorPrinter->hasErrors() ? 1 : 0;
    }
}
