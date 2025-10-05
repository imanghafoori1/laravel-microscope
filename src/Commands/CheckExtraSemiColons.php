<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class CheckExtraSemiColons extends BaseCommand
{
    use PatternApply;

    protected $signature = 'check:extra_semi_colons
     {--f|file=}
     {--d|folder=}
     {--F|except-file= : Comma seperated patterns for file names to exclude}
     {--D|except-folder= : Comma seperated patterns for folder names to exclude}
     {--nofix}';

    protected $description = 'Removes extra semi-colons.';

    public $customMsg = 'No extra semi-colons were found.  \(^_^)/';

    public function getPatterns()
    {
        return self::patterns($this->options->option('nofix'));
    }

    public static function patterns($noFix): array
    {
        return [
            'remove_extra_semi_colons' => [
                'cacheKey' => 'extra_semi_colons-v1',
                'search' => ';;',
                'replace' => $noFix ? null : ';',
            ],
        ];
    }
}
