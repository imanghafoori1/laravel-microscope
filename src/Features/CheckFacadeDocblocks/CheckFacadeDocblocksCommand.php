<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckFacadeDocblocks;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class CheckFacadeDocblocksCommand extends BaseCommand
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

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        FacadeDocblocks::$onFix = function ($class) {
            $this->line('- Fixed doc-blocks for: "'.$class.'"', 'fg=yellow');
        };

        FacadeDocblocks::$onError = function ($accessor, $file) {
            ErrorPrinter::singleton()->simplePendError('"'.$accessor.'"', $file, 20, 'asd', 'The Facade Accessor Not Found.');
        };

        $iterator->formatPrintPsr4Classmap();
    }
}
