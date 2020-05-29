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

    protected $signature = 'check:psr4 {--d|detailed : Show files being checked}';

    protected $description = 'Checks the validity of namespaces';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking PSR-4 Namespaces...');

        $errorPrinter->printer = $this->output;

        $autoload = ComposerJson::readAutoload();
        foreach ($autoload as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            CheckNamespaces::forNamespace($files, $psr4Path, $psr4Namespace, $this);
        }

        $this->replaceOldNamespace($autoload);
        $this->finishCommand($errorPrinter);
        $this->composerDumpIfNeeded($errorPrinter);
    }

    private function composerDumpIfNeeded($errorPrinter)
    {
        if ($errorPrinter->counts['badNamespace']) {
            $c = count($errorPrinter->counts['badNamespace']);
            $this->output->write('- '.$c.' Namespace'.($c > 1 ? 's' : '').' Fixed, Running: "composer dump"');
            app(Composer::class)->dumpAutoloads();
            $this->info('finished: "composer dump"');
        }
    }

    private function replaceOldNamespace(array $autoload)
    {
        $olds = array_keys(CheckNamespaces::$changedNamespaces);
        $news = array_values(CheckNamespaces::$changedNamespaces);
        foreach ($autoload as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            foreach ($files as $classFilePath) {
                $lines = file($classFilePath->getRealPath());
                $changed = false;
                foreach ($lines as $i => $line) {
                    $count = 0;
                    $lines[$i] = str_replace($olds, $news, $line, $count);
                    $count && $changed = true;
                }
                $changed && file_put_contents($classFilePath->getRealPath(), implode('', $lines));
            }
        }
    }
}
