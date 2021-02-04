<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\Checks\CheckIsQuery;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckBladeQueries extends Command
{
    use LogsErrors;

    protected $signature = 'check:blade_queries {--d|detailed : Show files being checked}';

    protected $description = 'Checks db queries in blade files';

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Checking blade files for db queries...');

        $errorPrinter->printer = $this->output;

        // checks the blade files for database queries.
        BladeFiles::check([CheckIsQuery::class]);

        $this->finishCommand($errorPrinter);
        $errorPrinter->printTime();

        return $errorPrinter->hasErrors() ? 1 : 0;
    }
}
