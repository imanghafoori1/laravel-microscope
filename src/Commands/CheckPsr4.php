<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
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

        $this->on('replacing_namespace', function ($path, $lineIndex, $lineContent) {
            $this->getOutput()->writeln(ErrorPrinter::getLink($path, $lineIndex));
            $this->info($lineContent);

            return $this->confirm('Do you want to change the old namespace?', true);
        });

        $this->option('nofix') && config(['microscope.no_fix' => true]);

        $autoloads = ComposerJson::readAutoload();
        start:
        $classes = CheckNamespaces::findAllClass(
            $autoloads,
            $this->option('detailed')
        );

        $errors = CheckNamespaces::findPsr4Errors($autoloads, $classes);

        $onFix = function ($path, $lineNumber) {
            app(ErrorPrinter::class)->simplePendError('', $path, $lineNumber, 'ns_replacement', 'Namespace replacement:');
        };
        $this->handleErrors($errors, $onFix);

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

    private function handleErrors(array $errors, $onFix)
    {
        foreach ($errors as $wrong) {
            if ($wrong['type'] === 'namespace') {
                $absPath = $wrong['absPath'];
                $from = $wrong['from'];
                $to = $wrong['to'];
                $class = $wrong['class'];
                $relPath = str_replace(base_path(), '', $absPath);

                $answer = CheckPsr4Printer::warnIncorrectNamespace($relPath, $from, $to, $class, $this);

                $answer && CheckNamespaces::changeNamespace($absPath, $from, $to, $class);

                ClassRefCorrector::fixAllRefs($onFix);
                CheckNamespaces::$changedNamespaces = [];
                app(ErrorPrinter::class)->fixedNamespace($absPath, $from, $to, 4);
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
