<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Imanghafoori\LaravelMicroscope\Checks\CheckEarlyReturn;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class CheckEarlyReturnsCommand extends BaseCommand
{
    protected $signature = 'check:early_returns
    {--s|nofix}
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $description = 'Applies the early return on the classes';

    protected $checks = [CheckEarlyReturn::class];

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        if ($this->options->option('nofix')) {
            $this->info(PHP_EOL.' Checking for possible code flattenings...'.PHP_EOL);
        }

        if (! $this->options->option('nofix') && ! $this->startWarning()) {
            return 0;
        }

        $nofix = $this->options->option('nofix');
        CheckEarlyReturn::$params = $this->getParams($nofix);
        $iterator->formatPrintForComposerLoadedFiles();
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
