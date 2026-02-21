<?php

namespace Imanghafoori\LaravelMicroscope\Features\SearchReplace;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\Console;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\SearchReplace\Finder;
use Imanghafoori\SearchReplace\Replacer;
use Imanghafoori\SearchReplace\Stringify;
use Imanghafoori\TokenAnalyzer\Refactor;

class PostReplaceAndSave
{
    public static $forceSave = false;

    public static function replaceAndSave($pattern, $matchedValue, $postReplaces, $namedPatterns, $tokens, $lineNum, $file, $newTokens): array
    {
        $to = Replacer::applyWithPostReplacements($pattern['replace'], $matchedValue['values'], $postReplaces, $namedPatterns);
        $countOldTokens = count($tokens);
        $tokens = self::save($matchedValue, $tokens, $to, $lineNum, $file, $newTokens);

        $tokens = token_get_all(Stringify::fromTokens($tokens));
        $diff = count($tokens) - $countOldTokens;
        $minCount = self::minimumMatchLength($pattern['search']);

        $i = $matchedValue['end'] + $diff + 1 - $minCount + 1;

        return [$tokens, $i];
    }

    private static function save($matchedValue, $tokens, $to, $lineNum, PhpFileDescriptor $file, $newTokens)
    {
        $from = Finder::getPortion($matchedValue['start'] + 1, $matchedValue['end'] + 1, $tokens);
        self::printLinks($lineNum, $file, $from, $to);

        if (self::$forceSave || self::askToRefactor($file)) {
            $file->putContents(Refactor::toString($newTokens));
            $file->setTokens($newTokens);
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

    private static function printLinks($lineNum, PhpFileDescriptor $file, $startingCode, $endResult)
    {
        $printer = ErrorPrinter::singleton();
        // Print Replacement Links
        $printer->print('Replacing:
'.Color::yellow(Str::limit($startingCode, 150)), '');
        $printer->print('With:
'.Color::yellow(Str::limit($endResult, 150)), '');

        $printer->print(Color::red('Replacement will occur at:'), '');
        $printer->count++;

        $lineNum && $printer->printLink($file, $lineNum);
    }

    private static function askToRefactor(PhpFileDescriptor $file)
    {
        $text = 'Do you want to replace '.Color::yellow($file->getFileName()).' with new version of it?';

        return Console::confirm($text);
    }
}
