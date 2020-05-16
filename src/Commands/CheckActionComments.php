<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Psr4Classes;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Checks\ActionsComments;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckActionComments extends Command
{
    use LogsErrors;

    protected $signature = 'check:action_comments';

    protected $description = 'Checks the validity of route definitions';

    /**
     * Execute the console command.
     *
     * @param  ErrorPrinter  $errorPrinter
     *
     * @return mixed
     */
    public function handle(ErrorPrinter $errorPrinter)
    {
        $t1 = microtime(true);
        $this->info('Checking route definitions...');

        $errorPrinter->printer = $this->output;

        $this->info('Commentify Route Actions...');

        ActionsComments::$command = $this;

        Psr4Classes::check([ActionsComments::class]);

        $this->finishCommand($errorPrinter);
        $t4 = microtime(true);

        $this->info('Total elapsed time:'.(($t4 - $t1)).' seconds');
    }
}
