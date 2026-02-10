<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckFacadeDocblocks;

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
        $iterator->formatPrintPsr4Classmap();
    }
}
