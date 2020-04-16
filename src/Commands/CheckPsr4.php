<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\CheckClasses;
use Imanghafoori\LaravelMicroscope\Contracts\FileCheckContract;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Traits\ScansFiles;
use Imanghafoori\LaravelMicroscope\Analyzers\Util;

class CheckPsr4 extends Command implements FileCheckContract
{
    use LogsErrors;
    use ScansFiles;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:psr4 {--d|detailed : Show files being checked}';

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

        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = CheckClasses::getAllPhpFiles($psr4Path);
            CheckClasses::checkAllClasses($files, $psr4Path, $psr4Namespace, $this);
        }

        $this->finishCommand($errorPrinter);
    }
}
