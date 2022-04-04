<?php

namespace Imanghafoori\LaravelMicroscope\SearchReplace;

use Illuminate\Support\Str;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\SearchReplace\Finder;
use Imanghafoori\SearchReplace\Replacer;
use Imanghafoori\SearchReplace\Stringify;
use Imanghafoori\TokenAnalyzer\Refactor;

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

            $i = 0;
            start:
            $namedPatterns = $pattern['named_patterns'] ?? [];
            $matchedValues = Finder::getMatches($pattern['search'], $tokens, $pattern['predicate'], $pattern['mutator'], $namedPatterns, $pattern['filters'], $i);

            if (! $matchedValues) {
                continue;
            }
            $postReplaces = $pattern['post_replace'] ?? [];
            if (! isset($pattern['replace'])) {
                foreach ($matchedValues as $matchedValue) {
                    self::show($matchedValue, $tokens, $absFilePath);
                }
                continue;
            }

            foreach ($matchedValues as $matchedValue) {
                [$newTokens, $lineNum] = Replacer::applyMatch(
                    $pattern['replace'],
                    $matchedValue,
                    $tokens,
                    $pattern['avoid_syntax_errors'] ?? false,
                    $pattern['avoid_result_in'] ?? [],
                    $postReplaces,
                    $namedPatterns
                );

                if ($lineNum === null) {
                    continue;
                }

                $to = Replacer::applyWithPostReplacements($pattern['replace'], $matchedValue['values'], $postReplaces, $namedPatterns);
                $countOldTokens = count($tokens);
                $tokens = self::save($matchedValue, $tokens, $to, $lineNum, $absFilePath, $newTokens);

                $tokens = token_get_all(Stringify::fromTokens($tokens));
                $diff = count($tokens) - $countOldTokens;
                $min_count = self::minimumMatchLength($pattern['search']);

                $i = $matchedValue['end'] + $diff + 1 - $min_count + 1;

                goto start;
            }
        }
    }

    private static function printLinks($lineNum, $absFilePath, $startingCode, $endResult)
    {
        $printer = app(ErrorPrinter::class);
        // Print Replacement Links
        $printer->print('Replacing:
<fg=yellow>'.Str::limit($startingCode, 150).'</>', '', 0);
        $printer->print('With:
<fg=yellow>'.Str::limit($endResult, 150).'</>', '', 0);

        $printer->print('<fg=red>Replacement will occur at:</>', '', 0);

        $lineNum && $printer->printLink($absFilePath, $lineNum, 0);
    }

    private static function askToRefactor($absFilePath)
    {
        $text = 'Do you want to replace '.basename($absFilePath).' with new version of it?';

        return app('current.command')->getOutput()->confirm($text, true);
    }

    private static function save($matchedValue, $tokens, $to, $lineNum, $absFilePath, $newTokens)
    {
        $from = Finder::getPortion($matchedValue['start'] + 1, $matchedValue['end'] + 1, $tokens);
        self::printLinks($lineNum, $absFilePath, $from, $to);

        if (self::askToRefactor($absFilePath)) {
            Filesystem::$fileSystem::file_put_contents($absFilePath, Refactor::toString($newTokens));
            $tokens = $newTokens;
        }

        return $tokens;
    }

    private static function minimumMatchLength($patternTokens)
    {
        $count = 0;
        foreach ($patternTokens as $token) {
            ! Finder::isOptional($token[1] ?? $token[0]) && $count++;
        }

        return $count;
    }

    private static function show($matchedValue, $tokens, $absFilePath)
    {
        $start = $matchedValue['start'] + 1;
        $end = $matchedValue['end'] + 1;

        $from = '';
        $lineNum = 0;
        for ($i = $start - 1; $i < $end; $i++) {
            ! $lineNum && $lineNum = ($tokens[$i][2] ?? 0);
            $from .= $tokens[$i][1] ?? $tokens[$i][0];
        }

        /**
         * @var $printer ErrorPrinter::class
         */
        $printer = app(ErrorPrinter::class);
        // Print Replacement Links
        $printer->print('Detected:
<fg=yellow>'.Str::limit($from, 150).'</>', '', 0);

        $printer->print('<fg=red>Found at:</>', '', 0);

        $lineNum && $printer->printLink($absFilePath, $lineNum, 0);
    }
}
