<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\SpyClasses\SpyDispatcher;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckEvents extends Command
{
    use LogsErrors;

    protected $signature = 'check:events';

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
        event('microscope.start.command');
        $this->info('Checking events...');

        $errorPrinter->printer = $this->output;

        event('microscope.finished.checks', [$this]);
        $this->getOutput()->writeln(' - '.SpyDispatcher::$listeningNum.' listenings were checked.');

        return $errorPrinter->pended ? 1 : 0;
    }
}
