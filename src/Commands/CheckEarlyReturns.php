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
        $this->info('Checking for Early Returns...');
        $this->warn('This command is going to make changes to your files!');
        $this->output->confirm('Do you have committed everything in git?', true);

        $psr4 = ComposerJson::readKey('autoload.psr-4');

        $fixed = 0;
        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            foreach ($files as $file) {
                $tokens = token_get_all(file_get_contents($file->getRealPath()));
                [$fixes, $tokens] = $this->refactor($tokens);

                ($fixes > 0)
                && $this->output->confirm('Do you want to flatten: '.$file->getRealPath(), true)
                && $this->fix($file, $tokens, $fixes)
                && $fixed++;
            }
        }

        if ($fixed > 0) {
            $msg = 'Hooraay !!!, '.$fixed.' files were flattened by laravel-microscope... ';
        } else {
            $msg = 'Congratulations, your code base does not seems to need any fix';
        }
        $this->info(PHP_EOL.$msg);
        $this->info('     \(^_^)/    You rock...   \(^_^)/    ');
    }

    private function fix($file, $tokens, $tries)
    {
        Refactor::saveTokens($file->getRealPath(), $tokens, $this->option('test'));

        $file = class_basename($file->getRealPath());
        $this->warn(PHP_EOL.$tries.' fixes applied to: '.$file);

        return true;
    }

    private function refactor($tokens)
    {
        $refactored = 1;
        $fixes = -1;
        while ($refactored > 0) {
            $fixes++;
            [$tokens, $refactored] = Refactor::flatten($tokens);
        }

        return [$fixes, $tokens];
    }
}
