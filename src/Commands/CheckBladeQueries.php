<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Traits\ScansFiles;
use Imanghafoori\LaravelMicroscope\Checks\CheckIsQuery;
use Imanghafoori\LaravelMicroscope\Contracts\FileCheckContract;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckBladeQueries extends Command implements FileCheckContract
{
    use LogsErrors;

    use ScansFiles;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:blade_queries {--d|detailed : Show files being checked}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks db queries in blade files';

    /**
     * Execute the console command.
     *
     * @param  ErrorPrinter  $errorPrinter
     *
     * @return int
     */
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
