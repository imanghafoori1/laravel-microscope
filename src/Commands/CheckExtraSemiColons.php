<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use JetBrains\PhpStorm\ExpectedValues;

class CheckExtraSemiColons extends Command
{
    use LogsErrors;
    use PatternApply;

    protected $signature = 'check:extra_semi_colons
     {--f|file=}
     {--d|folder=}
     {--nofix}';

    protected $description = 'Removes extra semi-colons.';

    protected $customMsg = 'No extra semi-colons were found.  \(^_^)/';

    #[ExpectedValues(values: [0, 1])]
    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Soaring like an eagle...');

        return $this->patternCommand($errorPrinter);
    }

    public function getPatterns()
    {
        return [
            'remove_extra_semi_colons' => [
                'cacheKey' => 'extra_semi_colons-v1',
                'search' => ';;',
                'replace' => $this->option('nofix') ? null : ';',
            ],
        ];
    }
}
