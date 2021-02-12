<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\CheckNamespaces;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;
use Symfony\Component\Finder\Finder;

class CheckPsr4 extends Command
{
    protected $signature = 'check:psr4 {--d|detailed : Show files being checked} {--f|force} {--s|nofix}';

    protected $description = 'Checks the validity of namespaces';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->line('');
        $this->info('Start checking PSR-4 namespaces...');
        $time = microtime(true);

        $errorPrinter->printer = $this->output;

        $autoload = ComposerJson::readAutoload();
        $this->checkNamespaces($autoload);
        $olds = \array_keys(CheckNamespaces::$changedNamespaces);
        $news = \array_values(CheckNamespaces::$changedNamespaces);

        $this->option('nofix') && config(['microscope.no_fix' => true]);

        if (! config('microscope.no_fix')) {
            $this->fixReferences($autoload, $olds, $news);
        }
        if (Str::startsWith(request()->server('argv')[1] ?? '', 'check:psr4')) {
            $this->getOutput()->writeln('');
            $this->getOutput()->writeln('<fg=green>Finished!</fg=green>');
            $this->getOutput()->writeln('==============================');
            $this->getOutput()->writeln('<options=bold;fg=yellow>'.CheckNamespaces::$checkedNamespaces.' classes were checked under:</>');
            $this->getOutput()->writeln(' - '.implode("\n - ", array_keys($autoload)).'');
            $this->printErrorsCount($errorPrinter, $time);
        } else {
            $this->getOutput()->writeln(' - '.CheckNamespaces::$checkedNamespaces.' namespaces were checked.');
        }

        $this->composerDumpIfNeeded($errorPrinter);
    }

    private function composerDumpIfNeeded(ErrorPrinter $errorPrinter)
    {
        if ($c = $errorPrinter->getCount('badNamespace')) {
            $this->output->write('- '.$c.' Namespace'.($c > 1 ? 's' : '').' Fixed, Running: "composer dump"');
            app(Composer::class)->dumpAutoloads();
            $this->info("\n".'finished: "composer dump"');
        }
    }

    private function fixRefs($_path, $olds, $news)
    {
        $lines = file($_path);
        $changed = [];
        $olds = $this->deriveVariants($olds);
        $news = $this->deriveVariants($news);
        foreach ($lines as $i => $line) {
            $count = 0;
            $lines[$i] = \str_replace($olds, $news, $line, $count);
            $count && $changed[] = ($i + 1);
        }

        $changed && \file_put_contents($_path, \implode('', $lines));

        return $changed;
    }

    private function bladeFilePaths()
    {
        $bladeFiles = [];
        $hints = self::getNamespacedPaths();
        $hints['1'] = View::getFinder()->getPaths();

        foreach ($hints as $paths) {
            foreach ($paths as $path) {
                $files = is_dir($path) ? Finder::create()->name('*.blade.php')->files()->in($path) : [];
                foreach ($files as $blade) {
                    /**
                     * @var \Symfony\Component\Finder\SplFileInfo $blade
                     */
                    $bladeFiles[] = $blade->getRealPath();
                }
            }
        }

        return $bladeFiles;
    }

    private static function getNamespacedPaths()
    {
        $hints = View::getFinder()->getHints();
        unset($hints['notifications'], $hints['pagination']);

        return $hints;
    }

    private function deriveVariants($olds)
    {
        $newOld = [];
        foreach ($olds as $old) {
            $newOld[] = $old.'(';
            $newOld[] = $old.'::';
            $newOld[] = $old.';';
            $newOld[] = $old."\n";
            $newOld[] = $old."\r";
        }

        return $newOld;
    }

    private function collectNonPsr4Paths()
    {
        $paths = [
            RoutePaths::get(),
            Paths::getAbsFilePaths(LaravelPaths::migrationDirs()),
            Paths::getAbsFilePaths(config_path()),
            Paths::getAbsFilePaths(LaravelPaths::factoryDirs()),
            Paths::getAbsFilePaths(app()->databasePath('seeds')),
            $this->bladeFilePaths(),
        ];

        return $this->mergePaths($paths);
    }

    private function mergePaths($paths)
    {
        $all = [];
        foreach ($paths as $p) {
            $all = array_merge($all, $p);
        }

        return $all;
    }

    private function fixReferences($autoload, $olds, $news)
    {
        foreach ($autoload as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            foreach ($files as $classFilePath) {
                $_path = $classFilePath->getRealPath();
                $lineNumbers = $this->fixRefs($_path, $olds, $news);
                foreach ($lineNumbers as $line) {
                    $this->report($_path, $line);
                }
            }
        }

        foreach ($this->collectNonPsr4Paths() as $_path) {
            $lineNumbers = $this->fixRefs($_path, $olds, $news);
            foreach ($lineNumbers as $line) {
                $this->report($_path, $line);
            }
        }
    }

    private function checkNamespaces(array $autoload)
    {
        foreach ($autoload as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            CheckNamespaces::within($files, $psr4Path, $psr4Namespace, $this);
        }
    }

    private function report(string $_path, $line)
    {
        app(ErrorPrinter::class)->simplePendError($_path, $line, '', 'ns_replacement', 'Namespace replacement:');
    }

    private function printErrorsCount($errorPrinter, $time)
    {
        if ($errorCount = $errorPrinter->errorsList['total']) {
            $errorCount && $this->warn(PHP_EOL.$errorCount.' error(s) found in namespaces');
        } else {
            $time = microtime(true) - $time;
            $this->line(PHP_EOL.'<fg=green>All namespaces are correct!</><fg=blue> You rock  \(^_^)/ </>');
            $this->line('<fg=red;options=bold>'.round($time, 5).'(s)</>');
            $this->line('');
        }
    }
}
