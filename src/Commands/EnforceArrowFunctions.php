<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class EnforceArrowFunctions extends Command
{
    use LogsErrors;
    use PatternApply;

    protected $signature = 'check:arrow_functions {--f|file=} {--d|folder=} {--nofix}';

    protected $description = 'Converts anonymous functions into arrow functions.';

    protected $customMsg = 'All the function are already converted into the arrow version.  \(^_^)/';

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Soaring like an eagle...');

        return $this->patternCommand($errorPrinter);
    }

    private function getPatterns(): array
    {
        return [
            'arrow_functions' => [
                'cacheKey' => 'arrow_fn-v1',
                'search' => 'function (<in_between>)<until>{return <statement>;}',
                'replace' => 'fn (<1>) => <3>',
            ],
        ];
    }
}
