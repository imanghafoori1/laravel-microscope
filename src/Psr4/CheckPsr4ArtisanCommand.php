<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use ImanGhafoori\ComposerJson\ComposerJson as Compo;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\TokenAnalyzer\GetClassProperties;

class CheckPsr4ArtisanCommand extends Command
{
    protected $signature = 'check:psr4 {--d|detailed : Show files being checked} {--f|force} {--s|nofix} {--w|watch} {--folder=}';

    protected $description = 'Checks the validity of namespaces';

    public static $checkedNamespacesStats = [];

    public static $buffer = 1000;

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->line('');
        $this->info('Started checking PSR-4 namespaces...');
        $time = microtime(true);

        $errorPrinter->printer = $this->output;

        $onCheck = $this->option('detailed') ? function ($path) {
            $this->line('Checking: '.$path);
        }
        : null;

        $autoloads = ComposerJson::readAutoload();
        $folder = ltrim($this->option('folder'), '=');
        start:
        $classLists = $this->getClasslists($autoloads, $onCheck, $folder);
        $errorsLists = $this->getErrorsLists($classLists, $autoloads);

        $time = round(microtime(true) - $time, 5);

        $this->fixErrors($errorsLists);
        $this->printReport($errorPrinter, $time, $autoloads);

        $this->composerDumpIfNeeded($errorPrinter);
        if ($this->option('watch')) {
            sleep(8);

            self::$checkedNamespacesStats = 0;
            $errorPrinter->errorsList = ['total' => 0];

            goto start;
        }
    }

    private function composerDumpIfNeeded(ErrorPrinter $errorPrinter)
    {
        if ($c = $errorPrinter->getCount('badNamespace')) {
            $this->output->write('- '.$c.' Namespace'.($c > 1 ? 's' : '').' Fixed, Running: "composer dump"');
            app(Composer::class)->dumpAutoloads();
            $this->info("\n".'Finished: "composer dump"');
        }
    }

    private function printReport(ErrorPrinter $errorPrinter, $time, $autoload)
    {
        $errorPrinter->logErrors();

        if (! $this->option('watch') && Str::startsWith(request()->server('argv')[1] ?? '', 'check:psr4')) {
            $this->getOutput()->writeln(CheckPsr4Printer::reportResult($autoload, self::$checkedNamespacesStats, $time));
            $this->printMessages(CheckPsr4Printer::getErrorsCount($errorPrinter->errorsList['total'], $time));
        } else {
            $this->getOutput()->writeln(' - '.array_sum(self::$checkedNamespacesStats).' namespaces were checked.');
        }
    }

    private function fixError($wrong, $beforeFix, $afterFix)
    {
        if ($wrong['type'] === 'namespace') {
            $absPath = $wrong['absPath'];
            $from = $wrong['from'];
            $to = $wrong['to'];
            $class = $wrong['class'];
            $relativePath = str_replace(base_path(), '', $absPath);

            CheckPsr4Printer::warnIncorrectNamespace($relativePath, $from, $class);
            if (CheckPsr4Printer::ask($this, $to)) {
                NamespaceFixer::fix($absPath, $from, $to);

                if ($from) {
                    $changes = [$from.'\\'.$class => $to.'\\'.$class];
                    ClassRefCorrector::fixAllRefs($changes, self::getAllPaths(), $beforeFix, $afterFix);
                }
                CheckPsr4Printer::fixedNamespace($relativePath, $from, $to);
            }
        } elseif ($wrong['type'] === 'filename') {
            CheckPsr4Printer::wrongFileName($wrong['relativePath'], $wrong['class'], $wrong['fileName']);
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

    protected function getClassesWithin($composerPath, $onCheck, $folder)
    {
        $results = [];
        foreach (FilePath::getAllPhpFiles($composerPath) as $classFilePath) {
            if ($folder && ! strpos($classFilePath, $folder)) {
                continue;
            }

            // Exclude blade files
            if (substr_count($classFilePath->getFilename(), '.') === 2) {
                continue;
            }

            $absFilePath = $classFilePath->getRealPath();

            [$currentNamespace, $class, $parent] = $this->readClass($absFilePath);

            // Skip if there is no class/trait/interface definition found.
            // For example a route file or a config file.
            if (! $class || $parent === 'Migration') {
                continue;
            }

            $onCheck && $onCheck($classFilePath->getRelativePathname());

            $results[] = [
                'currentNamespace' => $currentNamespace,
                'absFilePath' => $absFilePath,
                'class' => $class,
            ];
        }

        return $results;
    }

    private function getClasslists(array $autoloads, ?\Closure $onCheck, $folder)
    {
        $classLists = [];
        foreach (Compo::purgeAutoloadShortcuts($autoloads) as $path => $autoload) {
            $classLists[$path] = [];
            foreach ($autoload as $namespace => $psr4Path) {
                $classes = $this->getClassesWithin($psr4Path, $onCheck, $folder);
                self::$checkedNamespacesStats[$namespace] = count($classes);
                $classLists[$path] = array_merge($classLists[$path], $classes);
            }
        }

        return $classLists;
    }

    private function getErrorsLists(array $classLists, array $autoloads): array
    {
        $errorsLists = [];
        foreach ($classLists as $path => $classList) {
            $errorsLists[$path] = CheckNamespaces::findPsr4Errors(base_path(), $autoloads[$path], $classList);
        }

        return $errorsLists;
    }

    private function fixErrors(array $errorsLists)
    {
        $before = $this->beforeReferenceFix();
        $after = $this->afterReferenceFix();

        foreach ($errorsLists as $errors) {
            foreach ($errors as $wrong) {
                $this->fixError($wrong, $before, $after);
            }
        }
    }

    private function readClass($absFilePath): array
    {
        $buffer = self::$buffer;
        do {
            [
                $currentNamespace,
                $class,
                $type,
                $parent,
            ] = GetClassProperties::fromFilePath($absFilePath, $buffer);
            $buffer = $buffer + 1000;
        } while ($currentNamespace && ! $class && $buffer < 6000);

        return [$currentNamespace, $class, $parent];
    }
}
