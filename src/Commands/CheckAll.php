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

    protected $commandType = 'checks';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $t1 = microtime(true);
        $errorPrinter->printer = $this->output;

        // turns off error logging.
        $errorPrinter->logErrors = false;

        $callable = [
            'check:psr4' => ['--detailed' => $this->option('detailed'), '--nofix' => $this->option('nofix'), '--force' => $this->option('force')],
            'check:imports' => ['--nofix' => $this->option('nofix'), '--detailed' => $this->option('detailed')],
            'check:events' => [],
            'check:gates' => [],
            'check:views' => ['--detailed' => $this->option('detailed')],
            'check:routes'=> [],
            'check:stringy_classes' => [],
            'check:dd' => [],
            'check:dead_controllers' => [],
            'check:early_returns' => ['--nofix' => true],
            'check:bad_practices' => [],
        ];

        foreach ($callable as $command => $options) {
            $this->call($command, $options);
        }

        // turns on error logging.
        $errorPrinter->logErrors = true;

        $this->finishCommand($errorPrinter);
        $errorPrinter->printer->writeln('time: '.round(microtime(true) - $t1, 2).' (sec)', 2);

        if (random_int(1, 5) == 2 && Str::startsWith(request()->server('argv')[1] ?? '', 'check:al')) {
            ErrorPrinter::thanks($this);
        }

        return $errorPrinter->hasErrors() ? 1 : 0;
    }
}
