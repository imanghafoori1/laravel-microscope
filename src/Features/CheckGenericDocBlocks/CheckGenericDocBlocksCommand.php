<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckGenericDocBlocks;

use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class CheckGenericDocBlocksCommand extends BaseCommand
{
    protected $signature = 'check:generic_docblocks
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    {--nofix}
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
        GenericDocblocks::$conformer = $this->getConformer();

        $iterator->formatPrintPsr4();

        $this->info(GenericDocblocks::$foundCount.' generic doc-blocks were found.');
        $this->info(GenericDocblocks::$removedCount.' of them were removed.');
    }

    private function getConformer()
    {
        return $this->options->option('nofix') ? fn () => false : fn ($path) => $this->options->confirm($this->getQuestion($path), true);
    }

    private function getQuestion($absFilePath)
    {
        return 'Do you want to remove doc-blocks from: <fg=yellow>'.basename($absFilePath).'</>';
    }
}
