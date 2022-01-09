<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\CheckNamespaces;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Psr4\ClassRefCorrector;

class CheckPsr4 extends Command
{
    protected $signature = 'check:psr4 {--d|detailed : Show files being checked} {--f|force} {--s|nofix}';

    protected $description = 'Checks the validity of namespaces';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->line('');
        $this->info('Started checking PSR-4 namespaces...');
        $time = microtime(true);

        $errorPrinter->printer = $this->output;

        $handler = function ($relativePath, $currentNamespace, $correctNamespace) {
            if (! $this->option('nofix') && ErrorPrinter::ask($this, $correctNamespace)) {
                CheckNamespaces::doNamespaceCorrection(base_path($relativePath), $currentNamespace, $correctNamespace);
                // maybe an event listener
                app(ErrorPrinter::class)->badNamespace($relativePath, $correctNamespace, $currentNamespace);
            }
        };

        Event::listen('microscope.wrong_namespace', $handler);

        Event::listen('microscope.checking', function ($path) {
            $this->line('Checking: '.$path);
        });

        Event::listen('microscope.replacing_namespace', function ($_path, $lineIndex, $lineContent) {
            app(ErrorPrinter::class)->printLink($_path, $lineIndex);
            $this->info($lineContent);

            return $this->confirm('Do you want to change the old namespace?', true);
        });


        $autoload = ComposerJson::readAutoload();

        foreach ($autoload as $psr4Namespace => $psr4Path) {
            CheckNamespaces::within($psr4Path, $psr4Namespace, $this->option('detailed'));
        }

        $this->option('nofix') && config(['microscope.no_fix' => true]);

        if (CheckNamespaces::$changedNamespaces && ! config('microscope.no_fix')) {
            $olds = \array_keys(CheckNamespaces::$changedNamespaces);
            $news = \array_values(CheckNamespaces::$changedNamespaces);

            ClassRefCorrector::fixReferences($autoload, $olds, $news);
        }

        if (Str::startsWith(request()->server('argv')[1] ?? '', 'check:psr4')) {
            $this->reportResult($autoload);
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

    private function variants($namespaces)
    {
        $variants = [];
        foreach ($namespaces as $namespace) {
            $variants[] = '|'.$namespace.' ';
            $variants[] = ' '.$namespace.' ';
            $variants[] = ' \\'.$namespace.' ';
        }

        return $variants;
    }

    private function printErrorsCount($errorPrinter, $time)
    {
        if ($errorCount = $errorPrinter->errorsList['total']) {
            $this->warn(PHP_EOL.$errorCount.' error(s) found in namespaces');
        } else {
            $this->noErrorFound($time);
        }
    }

    private function reportResult($autoload)
    {
        $this->getOutput()->writeln('');
        $this->getOutput()->writeln('<fg=green>Finished!</fg=green>');
        $this->getOutput()->writeln('==============================');
        $this->getOutput()->writeln('<options=bold;fg=yellow>'.CheckNamespaces::$checkedNamespaces.' classes were checked under:</>');
        $this->getOutput()->writeln(' - '.implode("\n - ", array_keys($autoload)).'');
    }

    private function noErrorFound($time)
    {
        $time = microtime(true) - $time;
        $this->line(PHP_EOL.'<fg=green>All namespaces are correct!</><fg=blue> You rock  \(^_^)/ </>');
        $this->line('<fg=red;options=bold>'.round($time, 5).'(s)</>');
        $this->line('');
    }
}
