<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckAll extends Command
{
    use LogsErrors;

    protected $signature = 'check:all {--d|detailed : Show files being checked}';

    protected $description = 'Run all checks with one command.';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $t1 = microtime(true);
        $errorPrinter->printer = $this->output;

        //turns off error logging.
        $errorPrinter->logErrors = false;

        $this->call('check:psr4', ['--detailed' => $this->option('detailed')]);
        $this->call('check:events');
        $this->call('check:gates');
        $this->call('check:imports', ['--detailed' => $this->option('detailed')]);
        $this->call('check:views', ['--detailed' => $this->option('detailed')]);
        $this->call('check:routes');
        $this->call('check:stringy_classes');
        $this->call('check:dd');
        $this->call('check:bad_practices');

        //turns on error logging.
        $errorPrinter->logErrors = true;

        $this->finishCommand($errorPrinter);
        $this->info('Total elapsed time: '.(round(microtime(true) - $t1, 2)).' seconds');

        return $errorPrinter->hasErrors() ? 1 : 0;
    }
}
