<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Checks\PSR12\CurlyBraces;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\ActionComments\ActionsComments;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Iterators\ClassMapIterator;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckPsr12 extends Command
{
    use LogsErrors;

    protected $signature = 'check:psr12 {--f|file=} {--d|folder=}';

    protected $description = 'Applies psr-12 rules';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $errorPrinter->printer = $this->output;

        $this->info('Psr-12 is on the table...');
        $this->warn('This command is going to make changes to your files!');

        if (! $this->output->confirm('Do you have committed everything in git?', true)) {
            return;
        }

        ActionsComments::$command = $this;

        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');

        $check = [CurlyBraces::class];
        $psr4Stats = ForPsr4LoadedClasses::check($check, [], $fileName, $folder);
        $classMapStats = ClassMapIterator::iterate(base_path(), $check, [], $fileName, $folder);

        $this->getOutput()->writeln(implode(PHP_EOL, [
            Psr4Report::printAutoload($psr4Stats, $classMapStats),
        ]));

        $this->finishCommand($errorPrinter);

        return $errorPrinter->hasErrors() ? 1 : 0;
    }
}
