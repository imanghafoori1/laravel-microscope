<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
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

        $this->call('check:psr4', ['--detailed' => $this->option('detailed'), '--force' => $this->option('force')]);
        $this->call('check:events');
        $this->call('check:gates');
        $this->call('check:imports', ['--detailed' => $this->option('detailed')]);
        $this->call('check:views', ['--detailed' => $this->option('detailed')]);
        $this->call('check:routes');
        $this->call('check:stringy_classes');
        $this->call('check:dd');
        $this->call('check:dead_controllers');
        $this->call('check:bad_practices');

        //turns on error logging.
        $errorPrinter->logErrors = true;

        $this->finishCommand($errorPrinter);
        $this->info('Total elapsed time: '.(round(microtime(true) - $t1, 2)).' seconds');

        if (random_int(1, 2) == 2 && Str::startsWith(request()->server('argv')[1] ?? '', 'check:al')) {
            $this->info(PHP_EOL.'Heyman, If you find this package useful to you...');
            $this->info('Please contribute to it by sharing a post about it or give it an star on github.');
            $this->info('Reguards, Iman Ghafoori   (^_^) ');
            $this->info('https://github.com/imanghafoori1/microscope');
        }

        return $errorPrinter->hasErrors() ? 1 : 0;
    }
}
