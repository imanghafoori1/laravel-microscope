<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use ImanGhafoori\ComposerJson\ComposerJson as Comp;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;

class CheckPsr4ArtisanCommand extends Command
{
    private static $pathsForReferenceFix = [];

    protected $signature = 'check:psr4
        {--d|detailed : Show classes being checked}
        {--f|force : Fixes namespaces without asking.}
        {--s|nofix : Skips fixing namespaces and only reports them.}
        {--o|force-ref-fix : Fix references without asking.}
        {--r|no-ref-fix : Skips searching for references to fix them.}
        {--w|watch}
        {--folder=}';

    protected $description = 'Checks the validity of namespaces';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->line('');
        $this->info('Started checking PSR-4 namespaces...');
        $time = microtime(true);

        $errorPrinter->printer = $this->output;

        $composer = ComposerJson::make();
        $classLists = $this->getClassLists($composer);
        start:
        $errorsLists = $this->getErrorsLists($composer, $classLists);

        $time = round(microtime(true) - $time, 5);

        $this->handleErrors($errorsLists);
        $this->printReport($errorPrinter, $time, $composer->readAutoload(), $classLists);

        $this->composerDumpIfNeeded($errorPrinter);
        if ($this->option('watch')) {
            sleep(8);

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

    private function printReport(ErrorPrinter $errorPrinter, $time, $autoload, $classLists)
    {
        [$stats, $typesStats] = $this->countClasses($classLists);
        $errorPrinter->logErrors();

        if (! $this->option('watch') && Str::startsWith(request()->server('argv')[1] ?? '', 'check:psr4')) {
            $this->getOutput()->writeln(CheckPsr4Printer::reportResult($autoload, $stats, $time, $typesStats));
            $this->printMessages(CheckPsr4Printer::getErrorsCount($errorPrinter->errorsList['total'], $time));
        } else {
            $this->getOutput()->writeln(' - '.array_sum($stats).' namespaces were checked.');
        }
    }

    private function handleError($wrong, $beforeFix, $afterFix)
    {
        if ($wrong['type'] === 'namespace') {
            $absPath = $wrong['absFilePath'];
            $from = $wrong['currentNamespace'];
            $to = $wrong['correctNamespace'];
            $class = $wrong['class'];
            $relativePath = str_replace(base_path(), '', $absPath);

            CheckPsr4Printer::warnIncorrectNamespace($relativePath, $from, $class);

            if (CheckPsr4Printer::ask($this, $to)) {
                $this->fix($absPath, $from, $to, $class, $beforeFix, $afterFix, $relativePath);
            }
        } elseif ($wrong['type'] === 'filename') {
            CheckPsr4Printer::wrongFileName($wrong['relativePath'], $wrong['class'], $wrong['fileName']);
        }
    }

    private static function getPathForReferenceFix()
    {
        if (self::$pathsForReferenceFix) {
            return self::$pathsForReferenceFix;
        }

        $paths = [];

        foreach (ComposerJson::readAutoload() as $autoload) {
            foreach ($autoload as $psr4Path) {
                foreach (FilePath::getAllPhpFiles($psr4Path) as $file) {
                    $paths[] = $file->getRealPath();
                }
            }
        }

        $paths = array_merge(ComposerJson::readAutoloadFiles(), $paths);

        $paths = array_merge($paths, LaravelPaths::collectFilesInNonPsr4Paths());

        self::$pathsForReferenceFix = $paths;

        return $paths;
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
        if ($this->option('force-ref-fix')) {
            return function () {
                return true;
            };
        }

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

    private function handleErrors(array $errorsLists)
    {
        $before = $this->beforeReferenceFix();
        $after = $this->afterReferenceFix();

        foreach ($errorsLists as $errors) {
            foreach ($errors as $wrong) {
                $this->handleError($wrong, $before, $after);
            }
        }
    }

    private function countClasses($classLists)
    {
        $stats = [];
        $typesStats = [
            'enum' => 0,
            'interface' => 0,
            'class' => 0,
            'trait' => 0,
        ];

        foreach ($classLists as $composerPath => $classList) {
            foreach ($classList as $namespace => $classes) {
                $stats[$namespace] = count($classes);
                foreach ($classes as $class) {
                    $class['type'] === T_INTERFACE && $typesStats['interface']++;
                    $class['type'] === T_CLASS && $typesStats['class']++;
                    $class['type'] === T_TRAIT && $typesStats['trait']++;
                    $class['type'] === T_ENUM && $typesStats['enum']++;
                }
            }
        }

        return [$stats, $typesStats];
    }

    private function getPathFilter(string $folder)
    {
        return function ($absFilePath, $fileName) use ($folder) {
            return strpos($absFilePath, $folder);
        };
    }

    private function fix($absPath, $from, $to, $class, $beforeFix, $afterFix, $relativePath)
    {
        NamespaceFixer::fix($absPath, $from, $to);

        if ($from && ! $this->option('no-ref-fix')) {
            $changes = [
                $from.'\\'.$class => $to.'\\'.$class,
            ];

            ClassRefCorrector::fixAllRefs($changes, self::getPathForReferenceFix(), $beforeFix, $afterFix);
        }
        CheckPsr4Printer::fixedNamespace($relativePath, $from, $to);
    }

    private function getClassLists(Comp $composer)
    {
        $folder = ltrim($this->option('folder'), '=');
        $filter = function ($classFilePath, $currentNamespace, $class, $parent) {
            return $parent !== 'Migration';
        };

        $pathFilter = $folder ? $this->getPathFilter($folder) : null;

        return $composer->getClasslists($filter, $pathFilter);
    }

    private function getErrorsLists(Comp $composer, array $classLists)
    {
        $onCheck = $this->option('detailed') ? function ($class) {
            $msg = 'Checking: '.$class['currentNamespace'].'\\'.$class['class'];
            $this->line($msg);
        }
        : null;

        return $composer->getErrorsLists($classLists, $onCheck);
    }
}
