<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\CheckNamespaces;
use Imanghafoori\LaravelMicroscope\Contracts\FileCheckContract;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Traits\ScansFiles;
use Symfony\Component\Finder\Finder;

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
        $olds = array_keys(CheckNamespaces::$changedNamespaces);
        $news = array_values(CheckNamespaces::$changedNamespaces);
        foreach ($autoload as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            foreach ($files as $classFilePath) {
                $_path = $classFilePath->getRealPath();
                $lineNumbers = $this->fixRefs($_path, $olds, $news);
                foreach ($lineNumbers as $line) {
                    $errorPrinter->simplePendError($_path, $line, '', 'ns_replacement', 'Namespace replacement:');
                }
            }
        }

        foreach ($this->collectAllPaths() as $_path) {
            $lineNumbers = $this->fixRefs($_path, $olds, $news);
            foreach ($lineNumbers as $line) {
                $errorPrinter->simplePendError($_path, $line, '', 'ns_replacement', 'Namespace replacement:');
            }
        }

        $this->getOutput()->writeln(' - '.CheckNamespaces::$checkedNamespaces.' namespaces were Checked!');
        $this->finishCommand($errorPrinter);
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

        $changed && file_put_contents($_path, \implode('', $lines));

        return $changed;
    }

    private function bladeFilePaths()
    {
        $bladeFiles = [];
        $hints = self::getNamespacedPaths();
        $hints['1'] = View::getFinder()->getPaths();

        foreach ($hints as $paths) {
            foreach ($paths as $path) {
                $files = Finder::create()->name('*.blade.php')->files()->in($path);
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

    private function collectAllPaths()
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
}
