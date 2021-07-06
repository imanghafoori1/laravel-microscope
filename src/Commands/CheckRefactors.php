<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Analyzers\Refactor;
use Imanghafoori\LaravelMicroscope\Checks\CheckStringy;
use Imanghafoori\LaravelMicroscope\Checks\Refactorings;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckRefactors extends Command
{
    use LogsErrors;

    public static $checkedCallsNum = 0;

    protected $signature = 'refactor';

    protected $description = 'Does refactoring.';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking for refactors...');

        app()->singleton('current.command', function () {
            return $this;
        });

        $refactors = require base_path('/refactor.php');
        [$tokens_to_search_for, $placeholders] = self::parsePatterns($refactors);


        $errorPrinter->printer = $this->output;
        ForPsr4LoadedClasses::check([Refactorings::class], [$tokens_to_search_for, array_values($refactors), $placeholders]);
        $this->getOutput()->writeln(' - Finished refactors.');

        $this->finishCommand($errorPrinter);

        return $errorPrinter->hasErrors() ? 1 : 0;
    }
}
