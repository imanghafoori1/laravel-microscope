<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Imanghafoori\LaravelMicroscope\CheckNamespaces;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Contracts\FileCheckContract;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Traits\ScansFiles;

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
        $this->info('Checking PSR-4 Namespaces...');

        $errorPrinter->printer = $this->output;

        $psr4 = ComposerJson::readKey('autoload.psr-4');

        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            CheckNamespaces::forNamespace($files, $psr4Path, $psr4Namespace, $this);
        }

        $this->finishCommand($errorPrinter);
        $this->composerDumpIfNeeded($errorPrinter);
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
