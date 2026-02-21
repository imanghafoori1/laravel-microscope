<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckGenericDocBlocks;

use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Foundations\Console;

class CheckGenericDocBlocksCommand extends BaseCommand
{
    protected $signature = 'check:generic_docblocks
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    {--nofix : avoids deleting generic docblocks}
    ';

    protected $description = 'Removes generic doc-blocks from your controllers.';

    public $initialMsg = 'Removing generic doc-blocks...';

    public $checks = [GenericDocblocks::class];

    public $customMsg = '';

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        if ($this->options->option('nofix')) {
            Console::$forcedAnswer = false;
        }

        $iterator->formatPrintPsr4();

        $this->info(GenericDocblocks::$foundCount.' generic doc-blocks were found.');
    }
}
