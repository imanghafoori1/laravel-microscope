<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckAll extends Command
{
    use LogsErrors;

    protected $signature = 'check:all {--d|detailed : Show files being checked} {--f|force} {--s|nofix : avoids the automatic fixes}';

    protected $description = 'Run all checks with one command';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $t1 = microtime(true);
        $errorPrinter->printer = $this->output;

        // turns off error logging.
        $errorPrinter->logErrors = false;

        $this->call('check:psr4', ['--detailed' => $this->option('detailed'), '--nofix' => $this->option('nofix'), '--force' => $this->option('force')]);
        $this->call('check:imports', ['--nofix' => $this->option('nofix'), '--detailed' => $this->option('detailed')]);
        $this->call('check:events');
        $this->call('check:gates');
        $this->call('check:views', ['--detailed' => $this->option('detailed')]);
        $this->call('check:routes');
        $this->call('check:stringy_classes');
        $this->call('check:dd');
        $this->call('check:dead_controllers');
        $this->call('check:early_returns', ['--nofix' => true]);
        $this->call('check:bad_practices');

        // turns on error logging.
        $errorPrinter->logErrors = true;

        $this->finishCommand($errorPrinter);
        $this->info(''.(round(microtime(true) - $t1, 2)).' (s)');

        if (random_int(1, 5) == 2 && Str::startsWith(request()->server('argv')[1] ?? '', 'check:al')) {
            ErrorPrinter::thanks($this);
        }

        return $errorPrinter->hasErrors() ? 1 : 0;
    }
}
