<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\FileSystem\FileSystem;
use Imanghafoori\TokenAnalyzer\Refactor;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\SearchReplace\PatternParser;
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
                [$newTokens, $lineNum] = PatternParser::applyMatch($pattern['replace'], $matchedValue, $tokens, $pattern['avoid_result_in'] ?? []);
                if (! $newTokens) {
                    continue;
                }

                $tokens = self::saveOnDisk($matchedValue, $tokens, $pattern['replace'], $lineNum, $absFilePath, $newTokens);
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

    private static function saveOnDisk($matchedValue, $tokens, $replace, $lineNum, $absFilePath, $newTokens)
    {
        $from = TokenCompare::getPortion($matchedValue['start'], $matchedValue['end'], $tokens);
        $to = PatternParser::applyOnReplacements($replace, $matchedValue['values']);
        self::printLinks($lineNum, $absFilePath, $from, $to);

        if (self::askToRefactor($absFilePath)) {
            FileSystem::$fileSystem::file_put_contents($absFilePath, Refactor::toString($newTokens));
            $tokens = $newTokens;
        }

        return $tokens;
    }
}
