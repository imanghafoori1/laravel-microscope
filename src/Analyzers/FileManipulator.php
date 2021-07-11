<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;

class FileManipulator
{
    public static function removeLine($file, $_line = null)
    {
        $lineChanger = function ($lineNum) use ($_line) {
            // Replace only the first occurrence in the file
            if ($lineNum == $_line) {
                return '';
            }
        };

        return self::applyToEachLine($file, $lineChanger);
    }

    public static function fixImport($absPath, $import, $lineNum, $isAliased)
    {
        if (config('microscope.no_fix')) {
            return [false, []];
        }

        [$classBaseName, $correct] = self::getCorrect($import);

        if (\count($correct) !== 1) {
            return [false, $correct];
        }

        $hostNamespacedClass = NamespaceCorrector::getNamespacedClassFromRelativePath($absPath);
        // We just remove the wrong import if it is not needed.
        if (! $isAliased && NamespaceCorrector::haveSameNamespace($hostNamespacedClass, $correct[0])) {
            $chars = 'use '.$import.';';

            $result = self::replaceFirst($absPath, $chars, '', $lineNum);

            return [$result, [' Deleted!']];
        }

        return [self::replaceFirst($absPath, $import, $correct[0], $lineNum), $correct];
    }

    public static function replaceFirst($absPath, $search, $replace = '', $_line = null)
    {
        $lineChanger = function ($lineNum, $line, $isReplaced) use ($search, $replace, $_line) {
            // Replace only the first occurrence in the file
            if (! $isReplaced && strstr($line, $search)) {
                if (! $_line || $lineNum == $_line) {
                    return Str::replaceFirst($search, $replace, $line);
                }
            }
        };

        return self::applyToEachLine($absPath, $lineChanger);
    }

    public static function insertAtLine($absPath, $newLine, $atLine)
    {
        $lineChanger = function ($lineNum, $currentLine) use ($newLine, $atLine) {
            if ($lineNum == $atLine) {
                return $newLine.PHP_EOL.$currentLine;
            }
        };

        return self::applyToEachLine($absPath, $lineChanger);
    }

    public static function fixReference($absPath, $inlinedClassRef, $lineNum)
    {
        if (config('microscope.no_fix')) {
            return [false, []];
        }

        [$classBaseName, $correct] = self::getCorrect($inlinedClassRef);

        if (\count($correct) !== 1) {
            return [false, $correct];
        }

        $contextClassNamespace = NamespaceCorrector::getNamespacedClassFromRelativePath($absPath);
        // We just remove the wrong import if it is not needed.
        if (NamespaceCorrector::haveSameNamespace($contextClassNamespace, $correct[0])) {
            $baseName = trim(class_basename($correct[0]), '\\');

            return [self::replaceFirst($absPath, $inlinedClassRef, $baseName, $lineNum), $correct];
        }

        $uses = ParseUseStatement::parseUseStatements(token_get_all(file_get_contents($absPath)))[1];

        // if there is any use statement at the top
        if (count($uses) && ! isset($uses[$classBaseName])) {
            // replace in the class reference
            self::replaceFirst($absPath, $inlinedClassRef, $classBaseName, $lineNum);

            // insert a new import at the top
            $lineNum = array_values($uses)[0][1]; // first use statement

            $fullClassPath = $correct[0];

            return [self::insertAtLine($absPath, "use $fullClassPath;", $lineNum), $correct];
        }

        $uses = ParseUseStatement::parseUseStatements(token_get_all(file_get_contents($absPath)))[1];

        if (isset($uses[$classBaseName])) {
            return [self::replaceFirst($absPath, $inlinedClassRef, $classBaseName, $lineNum), $correct];
        }

        return [self::replaceFirst($absPath, $inlinedClassRef, $correct[0], $lineNum), $correct];
    }

    private static function applyToEachLine($absPath, $lineChanger)
    {
        $reading = fopen($absPath, 'r');
        $tmpFile = fopen($absPath.'._tmp', 'w');

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
            rename($absPath.'._tmp', $absPath);
        } else {
            unlink($absPath.'._tmp');
        }

        return $isReplaced;
    }

    private static function getCorrect($class)
    {
        $class_list = ForPsr4LoadedClasses::classList();
        $cls = \explode('\\', $class);
        $className = array_pop($cls);
        $correct = $class_list[$className] ?? [];

        return [$className, $correct];
    }
}
