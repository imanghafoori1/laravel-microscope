<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckGenericDocBlocks;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;

class CheckGenericDocBlocksCommand extends Command
{
    protected $signature = 'check:generic_docblocks {--f|file=} {--d|folder=} {--nofix}';

    protected $description = 'Removes generic doc-blocks from your controllers.';

    public function handle()
    {
        $this->info('Removing generic doc-blocks...');

        GenericDocblocks::$confirmer = $this->getConformer();

        $results = ForPsr4LoadedClasses::check([GenericDocblocks::class], [], ltrim($this->option('file'), '='), ltrim($this->option('folder'), '='));
        iterator_to_array($results);

        $this->info(GenericDocblocks::$foundCount.' generic doc-blocks were found.');
        $this->info(GenericDocblocks::$removedCount.' of them were removed.');

        return GenericDocblocks::$foundCount > 0 ? 1 : 0;
    }

    private function getConformer()
    {
        return $this->option('nofix') ? function () {
            return false;
        } : function ($path) {
            return $this->confirm($this->getQuestion($path), true);
        };
    }

    private function getQuestion($absFilePath)
    {
        return 'Do you want to remove doc-blocks from: <fg=yellow>'.basename($absFilePath).'</>';
    }
}
