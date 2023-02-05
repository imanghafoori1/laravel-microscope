<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\CheckPsr4Printer;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\Psr4\CheckNamespaces;
use Imanghafoori\LaravelMicroscope\Psr4\ClassRefCorrector;
use Imanghafoori\LaravelMicroscope\Psr4\NamespaceFixer;
use Imanghafoori\TokenAnalyzer\GetClassProperties;

class CheckPsr4 extends Command
{
    protected $signature = 'check:psr4 {--d|detailed : Show files being checked} {--f|force} {--s|nofix} {--w|watch}';

    protected $description = 'Checks the validity of namespaces';

    public static $checkedNamespaces = 0;

    public static $checkedNamespacesStats = [];

    public static $buffer = 2500;

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->line('');
        $this->info('Started checking PSR-4 namespaces...');
        $time = microtime(true);

        $errorPrinter->printer = $this->output;

        Event::listen('microscope.checking', function ($path) {
            $this->line('Checking: '.$path);
        });

        $this->option('nofix') && config(['microscope.no_fix' => true]);

        $autoloads = ComposerJson::readAutoload(true);
        start:
        $classes = [];
        foreach ($autoloads as $namespace => $psr4Path) {
            $classes = array_merge($classes, $this->getClassesWithin(
                $namespace,
                $psr4Path,
                $this->option('detailed'))
            );
        }

        $this->handleErrors(
            CheckNamespaces::findPsr4Errors(base_path(), $autoloads, $classes),
            $this->beforeReferenceFix(),
            $this->afterReferenceFix()
        );

        app(ErrorPrinter::class)->logErrors();
        $this->printReport(
            $errorPrinter,
            $time,
            $autoloads
        );

        $this->composerDumpIfNeeded($errorPrinter);
        if ($this->option('watch')) {
            sleep(8);

            CheckNamespaces::reset();
            app(ErrorPrinter::class)->errorsList = ['total' => 0];

            goto start;
        }
    }

    private function composerDumpIfNeeded(ErrorPrinter $errorPrinter)
    {
        if ($c = $errorPrinter->getCount('badNamespace')) {
            $this->output->write('- '.$c.' Namespace'.($c > 1 ? 's' : '').' Fixed, Running: "composer dump"');
            app(Composer::class)->dumpAutoloads();
            $this->info("\n".'finished: "composer dump"');
        }
    }

    private function printReport($errorPrinter, $time, $autoload)
    {
        if (! $this->option('watch') && Str::startsWith(request()->server('argv')[1] ?? '', 'check:psr4')) {
            $this->getOutput()->writeln(CheckPsr4Printer::reportResult($autoload, self::$checkedNamespaces));
            $this->printMessages(CheckPsr4Printer::getErrorsCount($errorPrinter, $time));
        } else {
            $this->getOutput()->writeln(' - '.self::$checkedNamespaces.' namespaces were checked.');
        }
    }

    private function handleErrors($errors, $beforeFix, $afterFix)
    {
        foreach ($errors as $wrong) {
            if ($wrong['type'] === 'namespace') {
                $absPath = $wrong['absPath'];
                $from = $wrong['from'];
                $to = $wrong['to'];
                $class = $wrong['class'];
                $relPath = str_replace(base_path(), '', $absPath);

                $answer = CheckPsr4Printer::warnIncorrectNamespace($relPath, $from, $to, $class, $this);

                if ($answer) {
                    NamespaceFixer::fix($absPath, $from, $to);

                    if ($from) {
                        $changes = [$from.'\\'.$class => $to.'\\'.$class];
                        ClassRefCorrector::fixAllRefs($changes, self::getAllPaths(), $beforeFix, $afterFix);
                    }
                    app(ErrorPrinter::class)->fixedNamespace($absPath, $from, $to, 4);
                }
            } elseif ($wrong['type'] === 'filename') {
                app(ErrorPrinter::class)->wrongFileName(
                    $wrong['relativePath'],
                    $wrong['class'],
                    $wrong['fileName']
                );
            }
        }
    }

    private static function getAllPaths()
    {
        $paths = [];

        foreach (ComposerJson::readAutoload() as $autoload) {
            foreach ($autoload as $psr4Path) {
                foreach (FilePath::getAllPhpFiles($psr4Path) as $file) {
                    $paths[] = $file->getRealPath();
                }
            }
        }

        return array_merge($paths, LaravelPaths::collectNonPsr4Paths());
    }

    private function afterReferenceFix()
    {
        return function ($path, $changedLineNums, $content) {
            Filesystem::$fileSystem::file_put_contents($path, $content);

            $p = app(ErrorPrinter::class);
            foreach ($changedLineNums as $line) {
                $p->simplePendError('', $path, $line, 'ns_replacement', 'Namespace replacement:');
            }
        };
    }

    private function beforeReferenceFix()
    {
        return function ($path, $lineIndex, $lineContent) {
            $this->getOutput()->writeln(ErrorPrinter::getLink($path, $lineIndex));
            $this->warn($lineContent);
            $msg = 'Do you want to update reference to the old namespace?';

            return $this->confirm($msg, true);
        };
    }

    private function printMessages($messages)
    {
        foreach ($messages as [$message, $level]) {
            $this->$level($message);
        }
    }

    protected function getClassesWithin($namespace, $composerPath, $detailed)
    {
        $results = [];
        foreach (FilePath::getAllPhpFiles($composerPath) as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();

            // Exclude blade files
            if (substr_count($absFilePath, '.') === 2) {
                continue;
            }

            [
                $currentNamespace,
                $class,
                $type,
                $parent,
            ] = GetClassProperties::fromFilePath($absFilePath, self::$buffer);

            // Skip if there is no class/trait/interface definition found.
            // For example a route file or a config file.
            if (! $class || $parent === 'Migration') {
                continue;
            }

            self::$checkedNamespaces++;

            if (isset(self::$checkedNamespacesStats[$namespace])) {
                self::$checkedNamespacesStats[$namespace]++;
            } else {
                self::$checkedNamespacesStats[$namespace] = 1;
            }

            $detailed && event('microscope.checking', [$classFilePath->getRelativePathname()]);

            $results[] = [
                'currentNamespace' => $currentNamespace,
                'absFilePath' => $absFilePath,
                'class' => $class,
            ];
        }

        return $results;
    }

    public static function reset()
    {
        self::$checkedNamespaces = 0;
    }
}
