<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\ClassRefCorrector;

use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class ClassRefCorrector
{
    /**
     * @var \Closure
     */
    private static $afterFix;

    /**
     * @var \Closure
     */
    private static $beforeFix;

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
     * @param  array<string, \iterable<int, string>>  $allPaths
     * @return void
     */
    private static function fixAllRefs($changes, $allPaths)
    {
        Loop::deepOver($allPaths, static fn ($path) => self::fix(PhpFileDescriptor::make($path), $changes));
    }

    /**
     * @param  PhpFileDescriptor  $path
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

    /**
     * @param  PhpFileDescriptor  $path
     * @param  array<string, string>  $changes
     * @return array
     */
    private static function fixRefs($path, $changes)
    {
        $lines = file($path->getAbsolutePath());
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

    /**
     * @param  string  $lineContent
     * @param  string[]  $olds
     * @return bool
     */
    private static function hasReference($lineContent, array $olds)
    {
        return self::strContains(
            str_replace(' ', '', $lineContent),
            self::possibleOccurrence($olds)
        );
    }

    /**
     * @param  string[]  $olds
     * @return \Generator<int, string>
     */
    private static function possibleOccurrence($olds)
    {
        $keywords = ['(', '::', ';', '|', ')', "\r\n", "\n", "\r", '$', '?', ',', '&'];

        foreach ($olds as $old) {
            foreach ($keywords as $keyword) {
                yield $old.$keyword;
            }
        }
    }

    /**
     * @param  string  $haystack
     * @param  string[]|\Generator<int, string>  $needles
     * @return bool
     */
    private static function strContains($haystack, $needles)
    {
        return Loop::any($needles, static fn ($needle) => mb_strpos($haystack, $needle) !== false);
    }
}
