<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckGate extends Command
{
    use LogsErrors;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:gate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the validity of gate definitions';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking gates...');

        $errorPrinter->printer = $this->output;

        $this->finishCommand($errorPrinter);
    }
}
