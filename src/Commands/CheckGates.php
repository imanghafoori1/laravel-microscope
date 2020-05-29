<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckGates extends Command
{
    use LogsErrors;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:gates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the validity of gate definitions';

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Checking gates...');

        $errorPrinter->printer = $this->output;

        $this->finishCommand($errorPrinter);
        event('microscope.finished.checks', [$this]);

        return $errorPrinter->pended ? 1: 0;
    }
}
