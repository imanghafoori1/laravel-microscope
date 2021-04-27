<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Imanghafoori\LaravelMicroscope\Psr4Classes;

class ReplaceLine
{
    public static function replaceFirst($file, $search, $replace = '', $_line = null)
    {
        $reading = fopen($file, 'r');
        $tmpFile = fopen($file.'._tmp', 'w');

        $isReplaced = false;

        $lineNum = 0;
        while (! feof($reading)) {
            $lineNum++;
            $line = fgets($reading);

            // replace only the first occurrence in the file
            if (! $isReplaced && strstr($line, $search)) {
                if (! $_line || $lineNum == $_line) {
                    $line = \str_replace($search, $replace, $line);
                    $isReplaced = $lineNum;
                }
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

    public static function fixReference($absPath, $class, $lineNum, $prefix = '')
    {
        if (config('microscope.no_fix')) {
            return [false, []];
        }

        $class_list = Psr4Classes::classList();
        $cls = \explode('\\', $class);
        $className = array_pop($cls);
        $correct = $class_list[$className] ?? [];

        $contextClass = self::getNamespaceFromRelativePath($absPath);

        if (\count($correct) !== 1) {
            return [false, $correct];
        }

        if (NamespaceCorrector::haveSameNamespace($contextClass, $correct[0])) {
            $correct[0] = trim(class_basename($correct[0]), '\\');
            $prefix = '';
        }

        return [self::replaceFirst($absPath, $class, $prefix.$correct[0], $lineNum), $correct];
    }

    public static function getNamespaceFromRelativePath($relPath)
    {
        // Remove .php from class path
        $relPath = str_replace([base_path(), '.php'], '', $relPath);

        $autoload = ComposerJson::readAutoload();
        uksort($autoload, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        $namespaces = array_keys($autoload);
        $paths = array_values($autoload);

        $relPath = \str_replace(DIRECTORY_SEPARATOR, '/', $relPath);

        return trim(\str_replace(['\\', '/'], DIRECTORY_SEPARATOR, \str_replace($paths, $namespaces, $relPath)), '\\');
    }
}
