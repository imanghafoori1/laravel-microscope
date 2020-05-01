<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\Refactor;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;

class CheckEarlyReturns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:early_returns {--t|test : backup the changed files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Applies the early return on the classes.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->startWarning();

        $psr4 = ComposerJson::readKey('autoload.psr-4');

        $totalNumberOfFixes = $fixedFilesCount = 0;
        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            foreach ($files as $file) {
                $path = $file->getRealPath();
                $tokens = token_get_all(file_get_contents($path));
                [$fixes, $tokens] = $this->refactor($tokens);

                if ($fixes <= 0) {
                    continue;
                }

                $this->getConfirm($path);
                $this->fix($path, $tokens, $fixes);
                $fixedFilesCount++;
                $totalNumberOfFixes += $fixes;
            }
        }

        $this->printFinalMsg($fixedFilesCount);
    }

    private function fix($filePath, $tokens, $tries)
    {
        Refactor::saveTokens($filePath, $tokens, $this->option('test'));

        $this->warn(PHP_EOL.$tries.' fixes applied to: '.class_basename($filePath));
    }

    private function refactor($tokens)
    {
        $fixes = 0;
        do {
            [$tokens, $refactored] = Refactor::flatten($tokens);
        } while ($refactored > 0 && $fixes++);

        return [$fixes, $tokens];
    }

    private function printFinalMsg($fixed)
    {
        if ($fixed > 0) {
            $msg = 'Hooraay !!!, '.$fixed.' files were flattened by laravel-microscope... ';
        } else {
            $msg = 'Congratulations, your code base does not seems to need any fix';
        }
        $this->info(PHP_EOL.$msg);
        $this->info('     \(^_^)/    You rock...   \(^_^)/    ');
    }

    private function getConfirm($filePath)
    {
        return $this->output->confirm('Do you want to flatten: '.$filePath, true);
    }

    private function startWarning()
    {
        $this->info('Checking for Early Returns...');
        $this->warn('This command is going to make changes to your files!');
        $this->output->confirm('Do you have committed everything in git?', true);
    }
}
