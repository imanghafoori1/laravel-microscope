<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Checks\CheckEarlyReturn;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Psr4ReportPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\ForComposerJsonFiles;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use JetBrains\PhpStorm\ExpectedValues;

class CheckEarlyReturns extends Command
{
    protected $signature = 'check:early_returns
    {--s|nofix}
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $description = 'Applies the early return on the classes';

    #[ExpectedValues(values: [0, 1])]
    public function handle()
    {
        ErrorPrinter::singleton($this->output);

        if ($this->option('nofix')) {
            $this->info(PHP_EOL.' Checking for possible code flattenings...'.PHP_EOL);
        }

        if (! $this->option('nofix') && ! $this->startWarning()) {
            return 0;
        }

        $pathDTO = PathFilterDTO::makeFromOption($this);
        $nofix = $this->option('nofix');
        $checkSet = CheckSet::init([CheckEarlyReturn::class], $pathDTO, $this->getParams($nofix));

        $lines = ForComposerJsonFiles::checkAndPrint($checkSet);
        Psr4ReportPrinter::printAll($lines, $this->getOutput());

        return ErrorPrinter::singleton()->hasErrors() ? 1 : 0;
    }

    private function startWarning()
    {
        $this->info(PHP_EOL.' Checking for Early Returns...');
        $this->warn(' Warning: This command is going to make "CHANGES" to your files!');

        return $this->output->confirm(' Do you have committed everything in git?');
    }

    private function getParams($nofix): array
    {
        return [
            'nofix' => $nofix,
            'nofixCallback' => function ($absPath) {
                $this->line('<fg=red>    - '.FilePath::getRelativePath($absPath).'</fg=red>');
            },
            'fixCallback' => function ($filePath, $tries) {
                $this->warn(PHP_EOL.$tries.' fixes applied to: '.class_basename($filePath));
            },
        ];
    }
}
