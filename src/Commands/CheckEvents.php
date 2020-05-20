<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckEvents extends Command
{
    use LogsErrors;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the validity of event listeners';

    /**
     * Execute the console command.
     *
     * @param  ErrorPrinter  $errorPrinter
     *
     * @return mixed
     */
    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking events...');

        $errorPrinter->printer = $this->output;

        $this->finishCommand($errorPrinter);
    }
}
