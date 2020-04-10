<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckAll extends Command
{
    use LogsErrors;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all checks with one command.';

    /**
     * Execute the console command.
     *
     * @param  ErrorPrinter  $errorPrinter
     *
     * @return mixed
     */
    public function handle(ErrorPrinter $errorPrinter)
    {
        $errorPrinter->printer = $this->output;

        //turns off error logging.
        $errorPrinter->logErrors = false;

        $this->call('check:view');
        $this->call('check:event');
        $this->call('check:gate');
        $this->call('check:import');
        $this->call('check:route');

        //turns on error logging.
        $errorPrinter->logErrors = true;

        $this->finishCommand($errorPrinter);
    }
}
