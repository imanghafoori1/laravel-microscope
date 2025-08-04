<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDeadControllers;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckDeadControllers extends Command
{
    use LogsErrors;

    protected $signature = 'check:dead_controllers
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $customMsg = 'No dead Controller Action was found!   \(^_^)/';

    protected $description = 'Checks that public controller methods have routes.';

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Checking for route-less controllers...');

        $errorPrinter->printer = $this->output;

        $pathDTO = PathFilterDTO::makeFromOption($this);
        $psr4Stats = ForPsr4LoadedClasses::check([RoutelessControllerActions::class], [], $pathDTO);

        Psr4Report::formatAndPrintAutoload($psr4Stats, [], $this->getOutput());

        $this->finishCommand($errorPrinter);
        $errorPrinter->printTime();

        return $errorPrinter->hasErrors() ? 1 : 0;
    }
}
