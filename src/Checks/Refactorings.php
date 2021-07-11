<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Refactor\PatternParser;

class Refactorings
{
    public static function check($tokens, $absFilePath, $classFilePath, $psr4Path, $psr4Namespace, $patterns)
    {
        $matches = PatternParser::findMatches($patterns[0], $tokens);
        if ($matches) {
            [$newVersion, $lineNums] = PatternParser::applyPatterns($patterns[1], $matches, $tokens);

            self::printLinks($lineNums, $absFilePath, $patterns[1]);

            self::askToRefactor($absFilePath) && file_put_contents($absFilePath, $newVersion);
        }
    }

    private static function printLinks($lineNums, $absFilePath, $patterns)
    {
        $printer = app(ErrorPrinter::class);
        // Print Replacement Links
        foreach ($patterns as $from => $to) {
            $printer->print('Replacing:    <fg=yellow>'.Str::limit($from).'</>', '', 0);
            $printer->print('With:         <fg=yellow>'.Str::limit($to).'</>', '', 0);
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
