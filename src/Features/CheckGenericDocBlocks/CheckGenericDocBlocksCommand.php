<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckGenericDocBlocks;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedPsr4Classes;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;

class CheckGenericDocBlocksCommand extends Command
{
    protected $signature = 'check:generic_docblocks
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    {--nofix}
    ';

    protected $description = 'Removes generic doc-blocks from your controllers.';

    public function handle()
    {
        $this->info('Removing generic doc-blocks...');

        GenericDocblocks::$conformer = $this->getConformer();
        $pathDTO = PathFilterDTO::makeFromOption($this);

        $psr4Stats = ForAutoloadedPsr4Classes::check([GenericDocblocks::class], [], $pathDTO);

        Psr4Report::formatAndPrintAutoload($psr4Stats, [], $this->getOutput());

        $this->info(GenericDocblocks::$foundCount.' generic doc-blocks were found.');
        $this->info(GenericDocblocks::$removedCount.' of them were removed.');

        return GenericDocblocks::$foundCount > 0 ? 1 : 0;
    }

    private function getConformer()
    {
        return $this->option('nofix') ? fn () => false : fn ($path) => $this->confirm($this->getQuestion($path), true);
    }

    private function getQuestion($absFilePath)
    {
        return 'Do you want to remove doc-blocks from: <fg=yellow>'.basename($absFilePath).'</>';
    }
}
