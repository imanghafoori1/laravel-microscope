<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckPsr12;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\ActionComments\ActionsComments;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedClassMaps;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedPsr4Classes;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckPsr12Command extends Command
{
    use LogsErrors;

    protected $signature = 'check:psr12
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $description = 'Applies psr-12 rules';

    protected $customMsg = 'All the public methods are marked explicitly as public.';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $errorPrinter->printer = $this->output;

        $this->info('Psr-12 is on the table...');
        $this->warn('This command is going to make changes to your files!');

        if (! $this->output->confirm('Do you have committed everything in git?', true)) {
            return;
        }

        ActionsComments::$command = $this;

        $pathFilterDTO = PathFilterDTO::makeFromOption($this);
        $check = [CurlyBracesCheck::class];
        $psr4Stats = ForAutoloadedPsr4Classes::check($check, [], $pathFilterDTO);
        $classMapStats = ForAutoloadedClassMaps::check(base_path(), $check, [], $pathFilterDTO);

        Psr4Report::formatAndPrintAutoload($psr4Stats, $classMapStats, $this->getOutput());
        $this->finishCommand($errorPrinter);

        return $errorPrinter->hasErrors() ? 1 : 0;
    }
}
