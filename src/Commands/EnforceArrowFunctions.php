<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class EnforceArrowFunctions extends BaseCommand
{
    use PatternApply;

    protected $signature = 'check:arrow_functions
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    {--nofix}';

    protected $description = 'Converts anonymous functions into arrow functions.';

    protected $customMsg = 'All the function are already converted into the arrow version.  \(^_^)/';

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
