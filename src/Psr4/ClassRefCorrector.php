<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;

class ClassRefCorrector
{
    private static $afterFix;

    private static $beforeFix;

    public static function fixAllRefs($changes, $beforeFix, $afterFix)
    {
        self::$afterFix = $afterFix;
        self::$beforeFix = $beforeFix;

        foreach (ComposerJson::readAutoload() as $autoload) {
            foreach ($autoload as $psr4Path) {
                foreach (FilePath::getAllPhpFiles($psr4Path) as $file) {
                    self::fix($file->getRealPath(), $changes);
                }
            }
        }

        foreach (LaravelPaths::collectNonPsr4Paths() as $path) {
            self::fix($path, $changes);
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

        $occurrences = [];
        foreach ($olds as $old) {
            foreach ($keywords as $keyword) {
                $occurrences[] = $old.$keyword;
            }
        }

        return $occurrences;
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
