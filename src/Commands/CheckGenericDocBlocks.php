<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Checks\GenericDocblocks;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckGenericDocBlocks extends Command
{
    use LogsErrors;

    protected $signature = 'check:generic_docblocks {--f|file=} {--d|folder=}';

    protected $description = 'Remove generic docblocks from your controllers.';

    protected $customMsg = 'Docblocks were removed.';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $errorPrinter->printer = $this->output;

        $this->info('removing generic docblocks...');

        GenericDocblocks::$command = $this;

        ForPsr4LoadedClasses::check([GenericDocblocks::class], [], ltrim($this->option('file'), '='), ltrim($this->option('folder'), '='));

        $this->finishCommand($errorPrinter);

        return $errorPrinter->hasErrors() ? 1 : 0;
    }
}
