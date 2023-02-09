<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;

class CheckPsr4ArtisanCommand extends Command
{
    protected $signature = 'check:psr4 {--d|detailed : Show files being checked} {--f|force} {--s|nofix} {--w|watch} {--folder=}';

    protected $description = 'Checks the validity of namespaces';

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
        $classLists = resolve(ClassListProvider::class)->getClasslists($autoloads, $onCheck, $folder);
        $errorsLists = $this->getErrorsLists($classLists, $autoloads);

        $time = round(microtime(true) - $time, 5);

        $this->fixErrors($errorsLists);
        $this->printReport($errorPrinter, $time, $autoloads);

        $this->composerDumpIfNeeded($errorPrinter);
        if ($this->option('watch')) {
            sleep(8);

            ClassListProvider::$checkedNamespacesStats = 0;
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
            $this->getOutput()->writeln(CheckPsr4Printer::reportResult($autoload, ClassListProvider::$checkedNamespacesStats, $time));
            $this->printMessages(CheckPsr4Printer::getErrorsCount($errorPrinter->errorsList['total'], $time));
        } else {
            $this->getOutput()->writeln(' - '.array_sum(ClassListProvider::$checkedNamespacesStats).' namespaces were checked.');
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
}
