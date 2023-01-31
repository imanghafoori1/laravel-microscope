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
                    self::fixAndReport($file->getRealPath(), $changes);
                }
            }
        }

        foreach (LaravelPaths::collectNonPsr4Paths() as $path) {
            self::fixAndReport($path, $changes);
        }
    }

    private static function fixAndReport($path, $changes)
    {
        $lineNumbers = self::fixRefs($path, $changes);

        foreach ($lineNumbers as $line) {
            $onFix = self::$afterFix;
            $onFix($path, $line);
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

        // saves the file into disk.
        $changedLineNums && Filesystem::$fileSystem::file_put_contents($path, \implode('', $lines));

        return $changedLineNums;
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
        return false !== mb_strpos(str_replace(' ', '', $lineContent), self::possibleOccurrence($olds));
    }
}
