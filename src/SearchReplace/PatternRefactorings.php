<?php

namespace Imanghafoori\LaravelMicroscope\SearchReplace;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\SearchReplace\Finder;
use Imanghafoori\SearchReplace\Replacer;
use JetBrains\PhpStorm\Pure;

class PatternRefactorings implements Check
{
    public static $patternFound = false;

    public static $patterns;

    public static function check(PhpFileDescriptor $file)
    {
        $absFilePath = $file->getAbsolutePath();

        foreach (self::$patterns as $pattern) {
            $cacheKey = $pattern['cacheKey'] ?? null;

            if ($cacheKey && CachedFiles::isCheckedBefore($cacheKey, $file)) {
                continue;
            }

            $tokens = $file->getTokens(true);

            if (isset($pattern['file']) && ! Str::endsWith($absFilePath, $pattern['file'])) {
                continue;
            }

            if (isset($pattern['directory']) && ! Str::startsWith($absFilePath, $pattern['directory'])) {
                continue;
            }

            $i = 0;
            start:
            $namedPatterns = $pattern['named_patterns'] ?? [];
            $matchedValues = Finder::getMatches($pattern['search'], $tokens, $pattern['predicate'], $pattern['mutator'], $namedPatterns, $pattern['filters'], $i, null, $pattern['ignore_whitespaces'] ?? true);

            if (! $matchedValues) {
                $cacheKey && CachedFiles::put($cacheKey, $file);
                continue;
            }

            $postReplaces = $pattern['post_replace'] ?? [];
            self::$patternFound = true;
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

                [$tokens, $i] = PostReplaceAndSave::replaceAndSave(
                    $pattern,
                    $matchedValue,
                    $postReplaces,
                    $namedPatterns,
                    $tokens,
                    $lineNum,
                    $absFilePath,
                    $newTokens
                );

                goto start;
            }
        }
    }

    private static function show($matchedValue, $tokens, $absFilePath)
    {
        [$message, $lineNum] = self::getShowMessage($matchedValue, $tokens);

        $printer = ErrorPrinter::singleton();
        $printer->addPendingError($absFilePath, $lineNum, 'pattern', 'Pattern Matched: ', $message);
    }

    #[Pure]
    private static function getShowMessage($matchedValue, $tokens): array
    {
        [$from, $lineNum] = self::getFrom($matchedValue, $tokens);
        $message = 'Matched Code: <fg=yellow>'.Str::limit($from, 150).'</>';

        return [$message, $lineNum];
    }

    public static function print(string $message, $absFilePath, $lineNum): void
    {
        $printer = ErrorPrinter::singleton();
        $printer->print($message, '', 0);
        $lineNum && $printer->printLink($absFilePath, $lineNum, 0);
    }

    private static function getFrom($matchedValue, $tokens): array
    {
        $start = $matchedValue['start'] + 1;
        $end = $matchedValue['end'] + 1;

        $from = '';
        $lineNum = 0;
        for ($i = $start - 1; $i < $end; $i++) {
            ! $lineNum && $lineNum = ($tokens[$i][2] ?? 0);
            $from .= $tokens[$i][1] ?? $tokens[$i][0];
        }

        return [$from, $lineNum];
    }
}
