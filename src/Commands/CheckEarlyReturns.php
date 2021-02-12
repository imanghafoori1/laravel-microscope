<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\Refactor;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckEarlyReturns extends Command
{
    protected $signature = 'check:early_returns {--t|test : backup the changed files} {--s|nofix}';

    protected $description = 'Applies the early return on the classes';

    public function handle()
    {
        if ($this->option('nofix')) {
            $this->info(PHP_EOL.' Checking for possible code flattenings...'.PHP_EOL);
        }

        if (! $this->option('nofix') && ! $this->startWarning()) {
            return;
        }

        $psr4 = ComposerJson::readAutoload();

        $fixingFilesCount = $totalNumberOfFixes = $fixedFilesCount = 0;
        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            foreach ($files as $file) {
                $path = $file->getRealPath();
                $tokens = token_get_all(file_get_contents($path));
                if (empty($tokens) || $tokens[0][0] !== T_OPEN_TAG) {
                    continue;
                }

                try {
                    [$fixes, $tokens] = $this->refactor($tokens);
                } catch (\Exception $e) {
                    dump('(O_o)   Well, It seems we had some problem parsing the contents of:  (O_o)');
                    dump('Skipping : '.$path);
                    continue;
                }

                $fixes !== 0 && $fixingFilesCount++;

                if ($this->option('nofix') && $fixes !== 0) {
                    $filePath = FilePath::getRelativePath($path);
                    $this->line("<fg=red>    - $filePath</fg=red>");
                    continue;
                }

                if ($fixes == 0 || ! $this->getConfirm($path)) {
                    continue;
                }

                $this->fix($path, $tokens, $fixes);
                $fixedFilesCount++;
                $totalNumberOfFixes += $fixes;
            }
        }

        $this->printFinalMsg($fixedFilesCount, $fixingFilesCount);

        return app(ErrorPrinter::class)->hasErrors() ? 1 : 0;
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

    private function printFinalMsg($fixed, $fixingFilesCount)
    {
        if ($fixed > 0) {
            $msg = ' Hooraay!!!, '.$fixed.' files were flattened by laravel-microscope!';
        } elseif ($fixingFilesCount == 0) {
            $msg = ' Congratulations, your code base does not seems to need any flattening. <fg=red> \(^_^)/ </fg=red>';
        } elseif ($fixingFilesCount !== 0 && $this->option('nofix')) {
            $msg = ' The files above can be flattened by: <fg=cyan>php artisan check:early</fg=cyan>';
        }

        isset($msg) && $this->info(PHP_EOL.$msg);
        $this->info(PHP_EOL);
        $this->line('========================================');
    }

    private function getConfirm($filePath)
    {
        $filePath = FilePath::getRelativePath($filePath);

        return $this->output->confirm(' Do you want to flatten: '.$filePath, true);
    }

    private function startWarning()
    {
        $this->info(PHP_EOL.' Checking for Early Returns...');
        $this->warn(' Warning: This command is going to make "CHANGES" to your files!');

        return $this->output->confirm(' Do you have committed everything in git?', false);
    }
}
