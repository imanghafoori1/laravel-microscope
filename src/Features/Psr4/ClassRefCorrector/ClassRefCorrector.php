<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\ClassRefCorrector;

use Imanghafoori\LaravelMicroscope\Foundations\Loop;

class ClassRefCorrector
{
    private static $afterFix = [AfterRefFix::class, 'getCallback'];

    private static $beforeFix = [BeforeRefFix::class, 'getCallback'];

    public static function fixOldRefs($from, $class, $to, $paths, $beforeFix = null, $afterFix = null)
    {
        $afterFix && self::$afterFix = $afterFix;
        $beforeFix && self::$beforeFix = $beforeFix;

        $changes = [
            $from.'\\'.$class => $to.'\\'.$class,
        ];

        self::fixAllRefs($changes, $paths);
    }

    /**
     * @param  array<string, string>  $changes
     * @param  array<string, \Generator<int, string>>  $allPaths
     * @return void
     */
    private static function fixAllRefs($changes, $allPaths)
    {
        foreach ($allPaths as $paths) {
            Loop::over($paths, fn ($path) => self::fix($path, $changes));
        }
    }

    /**
     * @param  string  $path
     * @param  array<string, string>  $changes
     * @return void
     */
    private static function fix($path, $changes)
    {
        [$changedLineNums, $content] = self::fixRefs($path, $changes);

        if ($changedLineNums) {
            // calling the \Closure:
            (self::$afterFix)($path, $changedLineNums, $content);
        }
    }

    private static function fixRefs($path, $changes)
    {
        $lines = file($path);
        $changedLineNums = [];
        $beforeFix = self::$beforeFix;
        $olds = array_keys($changes);
        $news = array_values($changes);
        foreach ($lines as $lineIndex => $lineContent) {
            if (self::hasReference($lineContent, $olds)) {
                if ($beforeFix($path, $lineIndex + 1, $lineContent) !== false) {
                    $count = 0;
                    $lines[$lineIndex] = str_replace($olds, $news, $lineContent, $count);
                    $count && $changedLineNums[] = $lineIndex + 1;
                }
            }
        }

        return [$changedLineNums, implode('', $lines)];
    }

    private static function hasReference($lineContent, array $olds)
    {
        return self::strContains(
            str_replace(' ', '', $lineContent),
            self::possibleOccurrence($olds)
        );
    }

    private static function possibleOccurrence($olds)
    {
        $keywords = ['(', '::', ';', '|', ')', "\r\n", "\n", "\r", '$', '?', ',', '&'];

        foreach ($olds as $old) {
            foreach ($keywords as $keyword) {
                yield $old.$keyword;
            }
        }
    }

    private static function strContains($haystack, $needles)
    {
        return Loop::any($needles, fn ($needle) => mb_strpos($haystack, $needle) !== false);
    }
}
