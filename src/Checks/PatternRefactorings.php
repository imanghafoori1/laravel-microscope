<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\Refactor;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\SearchReplace\PatternParser;

class PatternRefactorings
{
    public static function check($tokens, $absFilePath, $classFilePath, $psr4Path, $psr4Namespace, $patterns)
    {
        $matches = [];
        foreach ($patterns[0] as $pIndex => $pattern) {
            if (isset($pattern['file']) && ! Str::endsWith($absFilePath, $pattern['file'])) {
                continue;
            }

            if (isset($pattern['directory']) && ! Str::startsWith($absFilePath, $pattern['directory'])) {
                continue;
            }

            $matches = PatternParser::getMatch($pattern['search'], $tokens, $matches, $pIndex);
        }

        if ($matches) {
            [$newVersionTokens, $lineNums] = PatternParser::applyPatterns($patterns[1], $matches, $tokens);

            self::printLinks($lineNums, $absFilePath, $patterns[1]);

            self::askToRefactor($absFilePath) && file_put_contents($absFilePath, Refactor::toString($newVersionTokens));
        }
    }

    private static function printLinks($lineNums, $absFilePath, $patterns)
    {
        $printer = app(ErrorPrinter::class);
        // Print Replacement Links
        foreach ($patterns as $from => $to) {
            $printer->print('Replacing:    <fg=yellow>'.Str::limit($from).'</>', '', 0);
            $printer->print('With:         <fg=yellow>'.Str::limit($to['replace']).'</>', '', 0);
        }

        $printer->print('<fg=red>Replacement will occur at:</>', '', 0);

        foreach ($lineNums as $lineNum) {
            $lineNum && $printer->printLink($absFilePath, $lineNum, 0);
        }
    }

    private static function askToRefactor($absFilePath)
    {
        $text = 'Do you want to replace '.basename($absFilePath).' with new version of it?';

        return app('current.command')->getOutput()->confirm($text, true);
    }
}
