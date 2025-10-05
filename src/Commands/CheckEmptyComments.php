<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class CheckEmptyComments extends BaseCommand
{
    use PatternApply;

    protected $signature = 'check:empty_comments
     {--f|file=}
     {--d|folder=}
     {--F|except-file= : Comma seperated patterns for file names to exclude}
     {--D|except-folder= : Comma seperated patterns for folder names to exclude}
     {--nofix}';

    protected $description = 'Removes empty comments.';

    protected $customMsg = 'No empty comments were found.  \(^_^)/';

    public function getPatterns()
    {
        return [
            'delete_empty_comments' => [
                'cacheKey' => 'delete_empty_comments-v1',
                'search' => '<comment>',
                'replace' => $this->options->option('nofix') ? null : '',
                'predicate' => $this->getPredicate(),
            ],
        ];
    }

    private function getPredicate()
    {
        return function ($matches, $tokens) {
            if (($matches['values'][0][1] ?? '') !== '//') {
                return false;
            }
            $end = $matches['end'];

            $isBetweenSpace = (($tokens[$end - 1][0] ?? '') === T_WHITESPACE && ($tokens[$end + 1][0] ?? '') === T_WHITESPACE);

            $p2Type = ($tokens[$end - 2][0] ?? '');
            $n2Type = ($tokens[$end + 2][0] ?? '');

            if ($p2Type === T_COMMENT && $isBetweenSpace && $n2Type === T_COMMENT) {
                return false;
            }

            /*
             * Cases like these are not affected:
             *
             * $a = [
             *   //
             * ];
             *
             * public function __construct()
             * {
             *   //
             * }
             *
             */
            return ! in_array($p2Type, ['{', '[']) || ! $isBetweenSpace || ! in_array($n2Type, ['}', ']']);
        };
    }
}
