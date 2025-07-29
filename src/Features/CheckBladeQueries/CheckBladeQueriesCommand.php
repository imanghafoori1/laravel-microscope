<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckBladeQueries;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\BladeReport;
use Imanghafoori\LaravelMicroscope\Iterators\BladeFiles;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckBladeQueriesCommand extends Command
{
    use LogsErrors;

    protected $signature = 'check:blade_queries
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    {--detailed : Show files being checked}
    ';

    protected $description = 'Checks db queries in blade files';

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Checking blade files for db queries...');
        $pathDTO = PathFilterDTO::makeFromOption($this);

        $errorPrinter->printer = $this->output;

        // checks the blade files for database queries.
        $bladeStats = BladeFiles::check([IsQueryCheck::class], [], $pathDTO);

        $this->getOutput()->writeln(implode(PHP_EOL, [
            BladeReport::getBladeStats($bladeStats),
        ]));

        $this->finishCommand($errorPrinter);
        $errorPrinter->printTime();

        return $errorPrinter->hasErrors() ? 1 : 0;
    }
}
