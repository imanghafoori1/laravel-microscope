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

    public static function check(PhpFileDescriptor $file, $patterns)
    {
        $absFilePath = $file->getAbsolutePath();

        foreach ($patterns[0] as $pattern) {
            $cacheKey = $pattern['cacheKey'] ?? null;

            if ($cacheKey && CachedFiles::isCheckedBefore($cacheKey, $file)) {
                continue;
            }

            $tokens = $file->getTokens();

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

        self::print($message, $absFilePath, $lineNum);
    }

    #[Pure]
    private static function getShowMessage($matchedValue, $tokens): array
    {
        [$from, $lineNum] = self::getFrom($matchedValue, $tokens);
        $message = 'Detected:
<fg=yellow>'.Str::limit($from, 150).'</>
<fg=red>Found at:</>';

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
