<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\CheckClasses;
use Imanghafoori\LaravelMicroscope\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Util;

class CheckPsr4 extends Command
{
    use LogsErrors;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:psr4';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the validity of namespaces';

    /**
     * Execute the console command.
     *
     * @param  ErrorPrinter  $errorPrinter
     *
     * @return mixed
     */
    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking PSR-4 ...');

        $errorPrinter->printer = $this->output;

        $psr4 = Util::parseComposerJson('autoload.psr-4');

        $bar = $this->output->createProgressBar(count($psr4));
        $bar->start();

        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = CheckClasses::getAllPhpFiles($psr4Path);
            CheckClasses::checkAllClasses($files, $psr4Path, $psr4Namespace);

            $bar->advance();
        }

        $bar->finish();

        $this->finishCommand($errorPrinter);
    }
}
