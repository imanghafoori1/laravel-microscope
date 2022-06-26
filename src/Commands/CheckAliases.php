<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Checks\FacadeAliases;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class CheckAliases extends Command
{
    use LogsErrors;

    protected $signature = 'check:aliases {--f|file=} {--d|folder=} {--detailed : Show files being checked} {--s|nofix : avoids the automatic fixes}';

    protected $description = 'Replaces facade aliases with full namespace';

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Checking Aliases...');

        $this->option('nofix') && config(['microscope.no_fix' => true]);

        $errorPrinter->printer = $this->output;

        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');
        FacadeAliases::$command = $this;

        $paramProvider = function ($tokens) {
            $imports = ParseUseStatement::parseUseStatements($tokens);

            return $imports[0] ?: [$imports[1]];
        };
        ForPsr4LoadedClasses::check([FacadeAliases::class], $paramProvider, $fileName, $folder);

        $this->finishCommand($errorPrinter);

        $errorPrinter->printTime();

        return $errorPrinter->hasErrors() ? 1 : 0;
    }
}
