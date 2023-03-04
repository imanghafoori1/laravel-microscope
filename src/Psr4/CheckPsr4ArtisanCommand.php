<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use ImanGhafoori\ComposerJson\ComposerJson as Comp;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckPsr4ArtisanCommand extends Command
{
    protected $signature = 'check:psr4
        {--d|detailed : Show classes being checked}
        {--f|force : Fixes namespaces without asking.}
        {--s|nofix : Skips fixing namespaces and only reports them.}
        {--o|force-ref-fix : Fix references without asking.}
        {--r|no-ref-fix : Skips searching for references to fix them.}
        {--w|watch : Re-runs the command every 8 seconds.}
        {--folder= : Filter based on partial folder name.}';

    protected $description = 'Checks the validity of namespaces';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->line('');
        $this->info('Started checking PSR-4 namespaces...');
        $time = microtime(true);

        $errorPrinter->printer = $this->output;

        $composer = ComposerJson::make();
        start:
        $classLists = $this->getClassLists($composer);
        $errorsLists = $this->getErrorsLists($composer, $classLists);

        $time = round(microtime(true) - $time, 5);

        HandleErrors::handleErrors($errorsLists, $this);
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

    private function printMessages($messages)
    {
        foreach ($messages as [$message, $level]) {
            $this->$level($message);
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
