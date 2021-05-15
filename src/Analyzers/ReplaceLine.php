<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Imanghafoori\LaravelMicroscope\Psr4Classes;

class ReplaceLine
{
    public static function removeLine($file, $_line = null)
    {
        $lineChanger = function ($lineNum) use ($_line) {
            // Replace only the first occurrence in the file
            if ($lineNum == $_line) {
                return '';
            }
        };

        return self::applyToFile($file, $lineChanger);
    }

    public static function replaceFirst($file, $search, $replace = '', $_line = null)
    {
        $lineChanger = function ($lineNum, $line, $isReplaced) use ($search, $replace, $_line) {
            // Replace only the first occurrence in the file
            if (! $isReplaced && strstr($line, $search)) {
                if (! $_line || $lineNum == $_line) {
                    return \str_replace($search, $replace, $line);
                }
            }
        };

        return self::applyToFile($file, $lineChanger);
    }

    public static function fixReference($absPath, $class, $lineNum, $prefix = '', $isUsed = false)
    {
        if (config('microscope.no_fix')) {
            return [false, []];
        }

        $class_list = Psr4Classes::classList();
        $cls = \explode('\\', $class);
        $className = array_pop($cls);
        $correct = $class_list[$className] ?? [];

        $contextClass = NamespaceCorrector::getNamespaceFromRelativePath($absPath);

        if (\count($correct) !== 1) {
            return [false, $correct];
        }

        if (NamespaceCorrector::haveSameNamespace($contextClass, $correct[0])) {
            if ($isUsed) {
                return [self::removeLine($absPath, $lineNum), [' Deleted!']];
            }

            $correct[0] = trim(class_basename($correct[0]), '\\');
            $prefix = '';
        }

        return [self::replaceFirst($absPath, $class, $prefix.$correct[0], $lineNum), $correct];
    }

    private static function applyToFile($file, $lineChanger)
    {
        $reading = fopen($file, 'r');
        $tmpFile = fopen($file.'._tmp', 'w');

        $isReplaced = false;

        $lineNum = 0;
        while (! feof($reading)) {
            $lineNum++;
            $line = fgets($reading);

            $newLine = $lineChanger($lineNum, $line, $isReplaced);
            if (is_string($newLine)) {
                $line = $newLine;
                $isReplaced = true;
            }
            // Copy the entire file to the end
            fwrite($tmpFile, $line);
        }
        fclose($reading);
        fclose($tmpFile);
        // Might as well not overwrite the file if we didn't replace anything
        if ($isReplaced) {
            rename($file.'._tmp', $file);
        } else {
            unlink($file.'._tmp');
        }

        return $isReplaced;
    }
}
