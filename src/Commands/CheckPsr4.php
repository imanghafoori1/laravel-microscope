<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorReporters\CheckPsr4Printer;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Psr4\CheckNamespaces;
use Imanghafoori\LaravelMicroscope\Psr4\ClassRefCorrector;

class CheckPsr4 extends Command
{
    protected $signature = 'check:psr4 {--d|detailed : Show files being checked} {--f|force} {--s|nofix} {--w|watch}';

    protected $description = 'Checks the validity of namespaces';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->line('');
        $this->info('Started checking PSR-4 namespaces...');
        $time = microtime(true);

        $errorPrinter->printer = $this->output;

        Event::listen('microscope.checking', function ($path) {
            $this->line('Checking: '.$path);
        });

        $this->on('namespace_fixing', function ($relativePath, $currentNamespace, $correctNamespace, $class) {
            return CheckPsr4Printer::warnIncorrectNamespace($relativePath, $currentNamespace, $correctNamespace, $class, $this);
        });

        $this->on('namespace_fixed', [ErrorPrinter::class, 'fixedNamespace']);

        $this->on('replacing_namespace', function ($_path, $lineIndex, $lineContent) {
            app(ErrorPrinter::class)->printLink($_path, $lineIndex);
            $this->info($lineContent);

            return $this->confirm('Do you want to change the old namespace?', true);
        });

        $this->option('nofix') && config(['microscope.no_fix' => true]);

        $onFix = function ($path, $lineNumber) {
            app(ErrorPrinter::class)->simplePendError('', $path, $lineNumber, 'ns_replacement', 'Namespace replacement:');
        };

        start:
        $classes = CheckNamespaces::all($this->option('detailed'));

        $errors = $this->findPsr4Errors($classes);

        $this->handleErrors($errors);

        ClassRefCorrector::fixAllRefs($onFix);

        app(ErrorPrinter::class)->logErrors();
        $this->printReport($errorPrinter, $time);

        $this->composerDumpIfNeeded($errorPrinter);
        if ($this->option('watch')) {
            sleep(8);

            CheckNamespaces::reset();
            app(ErrorPrinter::class)->errorsList = ['total' => 0];

            goto start;
        }
    }

    private function on($event, $callback)
    {
        Event::listen('laravel_microscope.'.$event, $callback);
    }

    private function composerDumpIfNeeded(ErrorPrinter $errorPrinter)
    {
        if ($c = $errorPrinter->getCount('badNamespace')) {
            $this->output->write('- '.$c.' Namespace'.($c > 1 ? 's' : '').' Fixed, Running: "composer dump"');
            app(Composer::class)->dumpAutoloads();
            $this->info("\n".'finished: "composer dump"');
        }
    }

    private function printReport($errorPrinter, $time)
    {
        if (! $this->option('watch') && Str::startsWith(request()->server('argv')[1] ?? '', 'check:psr4')) {
            CheckPsr4Printer::reportResult($this);
            CheckPsr4Printer::printErrorsCount($errorPrinter, $time);
        } else {
            $this->getOutput()->writeln(' - '.CheckNamespaces::$checkedNamespaces.' namespaces were checked.');
        }
    }

    private function findPsr4Errors($classes)
    {
        $errors = [];
        foreach ($classes as $class) {
            $error = CheckNamespaces::checkNamespace($class['currentNamespace'], $class['absFilePath'], $class['class']);

            if ($error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    private function handleErrors(array $errors)
    {
        foreach ($errors as $wrong) {
            if ($wrong['type'] === 'namespace') {
                CheckNamespaces::changeNamespace(
                    $wrong['absPath'],
                    $wrong['from'],
                    $wrong['to'],
                    $wrong['class']
                );
            } elseif ($wrong['type'] === 'filename') {
                app(ErrorPrinter::class)->wrongFileName(
                    $wrong['relativePath'],
                    $wrong['class'],
                    $wrong['fileName']
                );
            }
        }
    }
}
