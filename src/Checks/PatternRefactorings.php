<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\FileSystem\FileSystem;
use Imanghafoori\TokenAnalyzer\Refactor;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\SearchReplace\PatternParser;
use Imanghafoori\SearchReplace\Stringify;
use Imanghafoori\SearchReplace\TokenCompare;

class PatternRefactorings
{
    public static function check($tokens, $absFilePath, $classFilePath, $psr4Path, $psr4Namespace, $patterns)
    {
        foreach ($patterns[0] as $pattern) {
            if (isset($pattern['file']) && ! Str::endsWith($absFilePath, $pattern['file'])) {
                continue;
            }

            if (isset($pattern['directory']) && ! Str::startsWith($absFilePath, $pattern['directory'])) {
                continue;
            }

            $matchedValues = TokenCompare::getMatches($pattern['search'], $tokens, $pattern['predicate'], $pattern['mutator']);

            if (! $matchedValues) {
                continue;
            }

            foreach ($matchedValues as $matchedValue) {
                [$newTokens, $lineNum] = PatternParser::applyMatch($pattern['replace'], $matchedValue, $tokens);
                if (isset($pattern['avoid_result_in'])) {
                    foreach ($pattern['avoid_result_in'] as $key) {
                        $_matchedValues = TokenCompare::getMatches(PatternParser::analyzeTokens($key), token_get_all(Stringify::fromTokens($newTokens)));
                        if ($_matchedValues) {
                            break;
                        }
                    }
                    if ($_matchedValues) {
                        continue;
                    }
                }
                self::printLinks($lineNum, $absFilePath, $patterns[1]);
                if (self::askToRefactor($absFilePath)) {
                    FileSystem::$fileSystem::file_put_contents($absFilePath, Refactor::toString($newTokens));
                    $tokens = $newTokens;
                }
            }
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

        $lineNums && $printer->printLink($absFilePath, $lineNums, 0);
    }

    private static function askToRefactor($absFilePath)
    {
        $text = 'Do you want to replace '.basename($absFilePath).' with new version of it?';

        return app('current.command')->getOutput()->confirm($text, true);
    }
}
