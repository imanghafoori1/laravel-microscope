<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Checks\CheckStringy;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;

class ClassifyStrings extends Command
{
    protected $signature = 'check:stringy_classes {--f|file=} {--d|folder=}';

    protected $description = 'Replaces string references with ::class version of them.';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking stringy classes...');
        app()->singleton('current.command', function () {
            return $this;
        });
        $errorPrinter->printer = $this->output;

        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');

        ForPsr4LoadedClasses::check([CheckStringy::class], [], $fileName, $folder);

        $this->getOutput()->writeln(' <fg='.config('microscope.colors.line_separator').'>✔ - Finished looking for stringy classes.</>');

        return $errorPrinter->hasErrors() ? 1 : 0;
    }
}
