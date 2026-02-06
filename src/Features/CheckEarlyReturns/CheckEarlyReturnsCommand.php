<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEarlyReturns;

use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

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
            return;
        }
        $this->info(PHP_EOL.' Checking for Early Returns...');

        $nofix = $this->options->option('nofix');
        CheckEarlyReturn::$params = $this->getParams($nofix);

        $iterator->formatPrintForComposerLoadedFiles();
    }

    private function startWarning()
    {
        $this->warn(' Warning: This command is going to make "CHANGES" to your files!');

        return $this->output->confirm(' Do you have committed everything in git?');
    }

    private function getParams($nofix): array
    {
        return [
            'nofix' => $nofix,
            'nofixCallback' => function (PhpFileDescriptor $file) {
                $this->line('    - '.Color::red($file->relativePath()));
            },
            'fixCallback' => function ($filePath, $tries) {
                $this->warn(PHP_EOL.$tries.' fixes applied to: '.Color::blue(class_basename($filePath)));
            },
        ];
    }
}
