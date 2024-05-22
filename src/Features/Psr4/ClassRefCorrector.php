<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class ClassRefCorrector
{
    private static $afterFix = [self::class, 'afterReferenceFix'];

    private static $beforeFix = [self::class, 'beforeReferenceFix'];

    public static function fixOldRefs($from, $class, $to, $path, $beforeFix = null, $afterFix = null)
    {
        $afterFix && self::$afterFix = $afterFix;
        $beforeFix && self::$beforeFix = $beforeFix;

        $changes = [
            $from.'\\'.$class => $to.'\\'.$class,
        ];

        self::fixAllRefs($changes, $path);
    }

    private static function fixAllRefs($changes, $paths)
    {
        foreach ($paths as $path) {
            foreach ($path as $p) {
                self::applyFix($p, $changes);
            }
        }
    }

    private static function applyFix($paths, $changes)
    {
        if (! is_string($paths)) {
            foreach (iterator_to_array($paths) as $path) {
                foreach ($path as $p) {
                    self::fix($p, $changes);
                }
            }
        } else {
            self::fix($paths, $changes);
        }
    }

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

    private static function afterReferenceFix()
    {
        return function (PhpFileDescriptor $file, $changedLineNums, $content) {
            $file->putContents($content);
            $path = $file->getAbsolutePath();

            $printer = ErrorPrinter::singleton();
            foreach ($changedLineNums as $line) {
                $printer->simplePendError(
                    '', $path, $line, 'ns_replacement', 'Namespace replacement:'
                );
            }
        };
    }

    private static function beforeReferenceFix($command)
    {
        if ($command->option('force-ref-fix')) {
            return function () {
                return true;
            };
        }

        return function (PhpFileDescriptor $file, $lineIndex, $lineContent) use ($command) {
            $command->getOutput()->writeln(
                ErrorPrinter::getLink($file->getAbsolutePath(), $lineIndex)
            );

            $command->warn($lineContent);

            return $command->confirm(self::getQuestion(), true);
        };
    }

    private static function getQuestion(): string
    {
        return 'Do you want to update reference to the old namespace?';
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
        foreach ($needles as $needle) {
            if (mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
