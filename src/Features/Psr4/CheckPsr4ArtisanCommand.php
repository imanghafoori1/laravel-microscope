<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4;

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

    public function handle()
    {
        $this->line('');
        $this->info('Started checking PSR-4 namespaces...');
        $time = microtime(true);
        $printer = ErrorPrinter::singleton($this->output);

        $composer = ComposerJson::make();
        start:
        $classLists = $this->getClassLists($composer);
        $errorsLists = $this->getErrorsLists($composer, $classLists);
        Psr4Errors::handle($errorsLists, $this);

        $duration = round(microtime(true) - $time, 5);
        $this->printReport($printer, $duration, $composer->readAutoload(), $classLists);
        $this->composerDumpIfNeeded($printer);

        if (! $this->option('watch')) {
            return $printer->total > 0 ? 1 : 0;
        }
        sleep(8);

        $printer->errorsList = [];
        $printer->total = 0;

        goto start;
    }

    private function composerDumpIfNeeded(ErrorPrinter $errorPrinter)
    {
        if ($c = $errorPrinter->getCount('badNamespace')) {
            $this->output->write('- '.$c.' Namespace'.($c > 1 ? 's' : ' ').' Fixed, Running: "composer dump"');
            app(Composer::class)->dumpAutoloads();
            $this->info("\n".'Finished: "composer dump"');
        }
    }

    private function printReport(ErrorPrinter $errorPrinter, $time, $autoload, $classLists)
    {
        $classListStatistics = self::countClasses($classLists);
        $errorPrinter->logErrors();

        if (! $this->option('watch') && Str::startsWith(request()->server('argv')[1] ?? '', 'check:psr4')) {
            $this->write(CheckPsr4Printer::reportResult($autoload, $time, $classListStatistics));
            $this->printMessages(CheckPsr4Printer::getErrorsCount($errorPrinter->total));
        } else {
            $this->write($this->getTotalCheckedMessage($classListStatistics->getTotalCount()));
        }
    }

    private function printMessages($messages)
    {
        foreach ($messages as [$message, $level]) {
            $this->$level($message);
        }
    }

    private static function countClasses($classLists)
    {
        $type = new TypeStatistics();

        foreach ($classLists as $composerPath => $classList) {
            foreach ($classList as $namespace => $entities) {
                $type->namespaceFiles($namespace, count($entities));
                foreach ($entities as $entity) {
                    $type->increment($entity['type']);
                }
            }
        }

        return $type;
    }

    private function getPathFilter($folder)
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

    private function getErrorsLists(Comp $composer, $classLists)
    {
        $onCheck = $this->option('detailed') ? function ($class) {
            $msg = 'Checking: '.$class['currentNamespace'].'\\'.$class['class'];
            $this->line($msg);
        }
        : null;

        return $composer->getErrorsLists($classLists, $onCheck);
    }

    private function getTotalCheckedMessage($total): string
    {
        return ' - '.$total.' namespaces were checked.';
    }

    private function write($text): void
    {
        $this->getOutput()->writeln($text);
    }
}
