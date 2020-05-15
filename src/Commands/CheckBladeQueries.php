<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Traits\ScansFiles;
use Imanghafoori\LaravelMicroscope\Checks\CheckModelClass;
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
     * @throws \ErrorException
     * @return mixed
     */
    public function handle(ErrorPrinter $errorPrinter)
    {
        $t1 = microtime(true);
        $this->info('Checking blade files for db queries...');

        $errorPrinter->printer = $this->output;

        // checks the blade files for database queries.
        BladeFiles::check([CheckModelClass::class]);

        $this->finishCommand($errorPrinter);
        $this->info('Total elapsed time:'.((microtime(true) - $t1)).' seconds');
    }
}
