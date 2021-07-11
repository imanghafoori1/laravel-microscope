<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Checks\Refactorings;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Refactor\PatternParser;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Mockery\Matcher\Pattern;

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
        $errorPrinter->printer = $this->output;

        try {
            $refactors = require base_path('/search_replace.php');
        } catch (\ErrorException $e) {
            file_put_contents(base_path('/search_replace.php'), '<?php
return ["your_pattern" => "replacement"];
');

            // print a msg
            return ;
        }
         $patterns = PatternParser::parsePatterns($refactors);

        ForPsr4LoadedClasses::check([Refactorings::class], [$patterns, $refactors]);
/*

        $this->getOutput()->writeln(' - Finished refactors.');

        $this->finishCommand($errorPrinter);

        return $errorPrinter->hasErrors() ? 1 : 0;*/
    }
}
