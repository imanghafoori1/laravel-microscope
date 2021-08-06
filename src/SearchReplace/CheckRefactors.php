<?php

namespace Imanghafoori\LaravelMicroscope\SearchReplace;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\SearchReplace\PatternParser;

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
            file_put_contents(base_path('/search_replace.php'), $this->stub());

            return;
        }

        $refactors = $this->normalizePatterns($refactors);
        $patterns = PatternParser::parsePatterns($refactors);

        ForPsr4LoadedClasses::check([PatternRefactorings::class], [$patterns, $refactors]);
        $this->getOutput()->writeln(' - Finished refactors.');

        $this->finishCommand($errorPrinter);

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private function stub()
    {
        return '<?php
return ["your_pattern" => "replacement"];
';
    }

    private function normalizePatterns($refactors)
    {
        foreach ($refactors as $i => $ref) {
            is_string($ref) && $refactors[$i] = ['replace' => $ref];

            isset($ref['directory']) && $refactors[$i]['directory'] = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $ref['directory']);
        }

        return $refactors;
    }
}
