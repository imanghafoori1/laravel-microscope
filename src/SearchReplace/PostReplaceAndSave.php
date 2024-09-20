<?php

namespace Imanghafoori\LaravelMicroscope\SearchReplace;

use Illuminate\Support\Str;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\SearchReplace\Finder;
use Imanghafoori\SearchReplace\Replacer;
use Imanghafoori\SearchReplace\Stringify;
use Imanghafoori\TokenAnalyzer\Refactor;

class PostReplaceAndSave
{
    public static function replaceAndSave($pattern, $matchedValue, $postReplaces, $namedPatterns, $tokens, $lineNum, string $absFilePath, $newTokens): array
    {
        $to = Replacer::applyWithPostReplacements($pattern['replace'], $matchedValue['values'], $postReplaces, $namedPatterns);
        $countOldTokens = count($tokens);
        $tokens = self::save($matchedValue, $tokens, $to, $lineNum, $absFilePath, $newTokens);

        $tokens = token_get_all(Stringify::fromTokens($tokens));
        $diff = count($tokens) - $countOldTokens;
        $minCount = self::minimumMatchLength($pattern['search']);

        $i = $matchedValue['end'] + $diff + 1 - $minCount + 1;

        return [$tokens, $i];
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

    private static function printLinks($lineNum, $absFilePath, $startingCode, $endResult)
    {
        $printer = ErrorPrinter::singleton();
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

        return ErrorPrinter::singleton()->printer->confirm($text);
    }
}