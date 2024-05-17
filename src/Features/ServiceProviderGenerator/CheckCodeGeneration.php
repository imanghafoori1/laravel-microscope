<?php

namespace Imanghafoori\LaravelMicroscope\Features\ServiceProviderGenerator;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\PhpFinder;

class CheckCodeGeneration extends Command
{
    protected $signature = 'check:generate';

    protected $description = 'Generates code';

    public function handle()
    {
        $this->info('Scanning for Empty Provider Files');
        ErrorPrinter::singleton($this->output);

        foreach (ComposerJson::readPsr4() as $psr4) {
            foreach ($psr4 as $psr4Namespace => $psr4Paths) {
                foreach ((array) $psr4Paths as $psr4Path) {
                    $files = PhpFinder::getAllPhpFiles($psr4Path);
                    GenerateCode::serviceProvider($files, $psr4Path, $psr4Namespace, $this);
                }
            }
        }
    }
}
