<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use JetBrains\PhpStorm\ExpectedValues;

class CheckEmptyComments extends Command
{
    use LogsErrors;
    use PatternApply;

    protected $signature = 'check:empty_comments {--f|file=} {--d|folder=} {--t|test : backup the changed files}';

    protected $description = 'removes empty comments.';

    protected $customMsg = 'No empty comments were found.  \(^_^)/';

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
            'empty_comments' => [
                'search' => '<comment>',
                'replace' => '',
                'predicate' => function ($matches, $tokens) {
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

                    return ! in_array($p2Type, ['{', '[']) || ! $isBetweenSpace || ! in_array($n2Type, ['}', ']']);
                },
            ],
        ];
    }
}
