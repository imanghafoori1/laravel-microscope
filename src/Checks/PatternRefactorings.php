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
                    $_matchedValues = self::hasAvoidedResult($pattern['avoid_result_in'], $newTokens);
                    if ($_matchedValues) {
                        continue;
                    }
                }

                $from = self::getPortion($matchedValue['start'], $matchedValue['end'], $tokens);
                $to = PatternParser::applyOnReplacements($pattern['replace'], $matchedValue['values']);
                self::printLinks($lineNum, $absFilePath, $from, $to);
                if (self::askToRefactor($absFilePath)) {
                    FileSystem::$fileSystem::file_put_contents($absFilePath, Refactor::toString($newTokens));
                    $tokens = $newTokens;
                }
            }
        }
    }

    private static function printLinks($lineNums, $absFilePath, $portion, $final_result)
    {
        $printer = app(ErrorPrinter::class);
        // Print Replacement Links
        $printer->print('Replacing:    <fg=yellow>'.Str::limit($portion).'</>', '', 0);
        $printer->print('With:         <fg=yellow>'.Str::limit($final_result).'</>', '', 0);

        $printer->print('<fg=red>Replacement will occur at:</>', '', 0);

        $lineNums && $printer->printLink($absFilePath, $lineNums, 0);
    }

    private static function askToRefactor($absFilePath)
    {
        $text = 'Do you want to replace '.basename($absFilePath).' with new version of it?';

        return app('current.command')->getOutput()->confirm($text, true);
    }

    private static function hasAvoidedResult($avoidResultIn, $newTokens)
    {
        foreach ($avoidResultIn as $key) {
            $_matchedValues = TokenCompare::getMatches(PatternParser::analyzeTokens($key), token_get_all(Stringify::fromTokens($newTokens)));
            if ($_matchedValues) {
                break;
            }
        }

        return $_matchedValues;
    }

    private static function getPortion($start, $end, $tokens)
    {
        $output = '';
        for ($i = $start - 1; $i < $end; $i++) {
            $output .= $tokens[$i][1] ?? $tokens[$i][0];
        }

        return $output;
    }
}
