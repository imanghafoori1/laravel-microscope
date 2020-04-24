<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckAll extends Command
{
    use LogsErrors;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:all {--d|detailed : Show files being checked}';

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

        $this->call('check:psr4', ['--detailed' => $this->option('detailed')]);
        $this->composerDumpIfNeeded($errorPrinter);
        $this->call('check:events');
        $this->call('check:gates');
        $this->call('check:imports', ['--detailed' => $this->option('detailed')]);
        $this->call('check:views', ['--detailed' => $this->option('detailed')]);
        $this->call('check:routes');

        //turns on error logging.
        $errorPrinter->logErrors = true;

        $this->finishCommand($errorPrinter);
    }

    private function composerDumpIfNeeded($errorPrinter)
    {
        if ($errorPrinter->counts['badNamespace']) {
            $c = count($errorPrinter->counts['badNamespace']);
            $this->output->write('- '.$c.' Namespace'.($c > 1 ? 's' : '').' Fixed, Running: "composer dump"');
            app(Composer::class)->dumpAutoloads();
        }
    }
}
