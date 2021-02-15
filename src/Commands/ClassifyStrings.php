<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Checks\CheckStringy;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Psr4Classes;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class ClassifyStrings extends Command
{
    use LogsErrors;

    public static $checkedCallsNum = 0;

    protected $signature = 'check:stringy_classes';

    protected $description = 'Replaces string references with ::class version of them';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking stringy classes...');
        app()->singleton('current.command', function () {
            return $this;
        });
        $errorPrinter->printer = $this->output;
        Psr4Classes::check([CheckStringy::class]);
        $this->getOutput()->writeln(' - Finished looking for stringy classes.');

        $this->finishCommand($errorPrinter);

        return $errorPrinter->hasErrors() ? 1 : 0;
    }
}
