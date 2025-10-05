<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class CheckAbortIf extends BaseCommand
{
    use PatternApply;

    protected $signature = 'check:abort_if
     {--f|file=}
     {--d|folder=}
     {--F|except-file= : Comma seperated patterns for file names to exclude}
     {--D|except-folder= : Comma seperated patterns for folder names to exclude}
     {--nofix}';

    protected $description = 'Refactor using abort_if function.';

    protected $customMsg = 'No refactor opportunity was found.  \(^_^)/';

    public function getPatterns()
    {
        return [
            'abort_if-1' => [
                'cacheKey' => 'abort_if-code-v1',
                'search' => 'if(<in_between>){abort();}',
                'replace' => $this->options->option('nofix') ? null : 'abort_if(<1>);',
            ],
            'abort_if-2' => [
                'cacheKey' => 'abort_if-code-v2',
                'search' => 'if(<in_between>){abort(<in_between>);}',
                'replace' => $this->options->option('nofix') ? null : 'abort_if(<1>, <2>);',
            ],
        ];
    }
}
