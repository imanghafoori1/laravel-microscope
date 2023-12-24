<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4;

class ClassRefCorrector
{
    private static $afterFix;

    private static $beforeFix;

    public static function fixAllRefs($changes, $paths, $beforeFix, $afterFix)
    {
        self::$afterFix = $afterFix;
        self::$beforeFix = $beforeFix;
        foreach ($paths as $path) {
            foreach ($path as $p) {
                self::fix($p, $changes);
            }
        }
    }

    private static function fix($path, $changes)
    {
        [$changedLineNums, $content] = self::fixRefs($path, $changes);

        if ($changedLineNums) {
            $afterFix = self::$afterFix;
            $afterFix($path, $changedLineNums, $content);
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

    private static function possibleOccurrence($olds)
    {
        $keywords = ['(', '::', ';', '|', ')', "\r\n", "\n", "\r", '$', '?', ',', '&'];

        foreach ($olds as $old) {
            foreach ($keywords as $keyword) {
                yield $old.$keyword;
            }
        }
    }

    private static function hasReference($lineContent, array $olds)
    {
        return self::str_contains(
            str_replace(' ', '', $lineContent),
            self::possibleOccurrence($olds)
        );
    }

    private static function str_contains($haystack, $needles)
    {
        foreach ($needles as $needle) {
            if (mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
