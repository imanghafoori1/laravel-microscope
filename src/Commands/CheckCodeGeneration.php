<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\GenerateCode;

class CheckCodeGeneration extends Command
{
    protected $signature = 'check:generate';

    protected $description = 'Generates code';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Scanning for Empty Provider Files');
        $errorPrinter->printer = $this->output;

        $autoload = ComposerJson::readAutoload();

        foreach ($autoload as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            GenerateCode::serviceProvider($files, $psr4Path, $psr4Namespace, $this);
        }
    }
}
