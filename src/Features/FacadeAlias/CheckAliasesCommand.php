<?php

namespace Imanghafoori\LaravelMicroscope\Features\FacadeAlias;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class CheckAliasesCommand extends Command
{
    use LogsErrors;

    protected $signature = 'check:aliases {--f|file=} {--d|folder=} {--detailed : Show files being checked} {--s|nofix : avoids the automatic fixes}';

    protected $description = 'Replaces facade aliases with full namespace';

    protected $finishMsg = '✅  Finished checking for facade aliases.';

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info(' 🔍 Looking Facade Aliases...');

        $errorPrinter->printer = $this->output;

        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');
        FacadeAliasesCheck::$command = $this->getOutput();

        if ($this->option('nofix')) {
            FacadeAliasesCheck::$handler = FacadeAliasReporter::class;
        }

        $paramProvider = function ($tokens) {
            $imports = ParseUseStatement::parseUseStatements($tokens);

            return $imports[0] ?: [$imports[1]];
        };
        $results = ForPsr4LoadedClasses::check([FacadeAliasesCheck::class], $paramProvider, $fileName, $folder);
        iterator_to_array($results);

        $this->info(PHP_EOL.' '.$this->finishMsg);

        $errorPrinter->printTime();

        return FacadeAliasReporter::$errorCount > 0 ? 1 : 0;
    }
}
