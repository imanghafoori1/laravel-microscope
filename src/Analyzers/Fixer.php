<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use ImanGhafoori\ComposerJson\NamespaceCalculator;
use Imanghafoori\Filesystem\FileManipulator;
use Imanghafoori\LaravelMicroscope\ClassListProvider;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class Fixer
{
    public static function isInUserSpace($class): bool
    {
        $class = ltrim($class, '\\');
        $segments = explode('\\', $class);
        $baseClassName = array_pop($segments);

        if (class_exists($baseClassName) || interface_exists($baseClassName)) {
            return false;
        }

        return self::compare($class);
    }

    private static function compare($class)
    {
        foreach (ComposerJson::readPsr4() as $autoload) {
            if (self::startsWith($class, array_keys($autoload))) {
                return true;
            }
        }

        return false;
    }

    private static function startsWith($haystack, $needles)
    {
        foreach ($needles as $needle) {
            if (0 === strncmp($haystack, $needle, strlen($needle))) {
                return true;
            }
        }

        return false;
    }

    private static function guessCorrect($classBaseName)
    {
        return ClassListProvider::get()[$classBaseName] ?? [];
    }

    public static function fixReference($absPath, $inlinedClassRef, $lineNum)
    {
        $classBaseName = class_basename($inlinedClassRef);

        $correct = self::guessCorrect($classBaseName);

        if (count($correct) !== 1) {
            return [false, $correct];
        }
        $fullClassPath = $correct[0];

        $file = PhpFileDescriptor::make($absPath);
        $contextClassNamespace = $file->getNamespace();

        if (NamespaceCalculator::haveSameNamespace($contextClassNamespace, $fullClassPath)) {
            return [self::doReplacement($file, $inlinedClassRef, class_basename($fullClassPath), $lineNum), $correct];
        }

        $uses = ParseUseStatement::parseUseStatements(token_get_all(file_get_contents($absPath)))[1];

        // if there is some use statements at the top but the class is not imported.
        if (count($uses) === 0 || isset($uses[$classBaseName])) {
            if (count($uses) === 0 && $fullClassPath[0] !== '\\') {
                $fullClassPath = '\\'.$fullClassPath;
            }

            if (isset($uses[$classBaseName]) && $uses[$classBaseName][0] === $fullClassPath) {
                $fullClassPath = $classBaseName;
            }

            return [self::doReplacement($file, $inlinedClassRef, $fullClassPath, $lineNum), $correct];
        }

        // Replace in the class reference
        self::doReplacement($file, $inlinedClassRef, $classBaseName, $lineNum);

        // Insert a new import at the top
        $lineNum = array_values($uses)[0][1]; // first use statement

        if (
            ! class_exists($fullClassPath) &&
            ! interface_exists($fullClassPath) &&
            ! trait_exists($fullClassPath) &&
            ! (function_exists('enum_exists') && enum_exists($fullClassPath))
        ) {
            return [false, []];
        }

        return [FileManipulator::insertNewLine($absPath, "use $fullClassPath;", $lineNum), $correct];
    }

    public static function fixImport($absPath, $import, $lineNum, $isAliased)
    {
        $correct = self::guessCorrect(class_basename($import));

        if (count($correct) !== 1) {
            return [false, $correct];
        }

        $file = PhpFileDescriptor::make($absPath);
        $hostNamespacedClass = $file->getNamespace();
        // We just remove the wrong import if it is not needed.
        if (! $isAliased && NamespaceCalculator::haveSameNamespace($hostNamespacedClass, $correct[0])) {
            $lines = $file->searchReplacePatterns("use $import;", '');

            return [$lines, [' Deleted!']];
        }

        $lines = $file->searchReplacePatterns("use $import;", 'use '.$correct[0].';'.PHP_EOL);

        return [$lines, $correct];
    }

    private static function doReplacement(PhpFileDescriptor $file, $wrongRef, $correctRef, $lineNum)
    {
        if (self::phpVersionIsMoreOrEqualTo('8.0.0')) {
            return $file->replaceAtLine($wrongRef, $correctRef, $lineNum);
        }

        return (bool) $file->searchReplacePatterns($wrongRef, $correctRef);
    }

    private static function phpVersionIsMoreOrEqualTo($version): bool
    {
        return version_compare(PHP_VERSION, $version) !== -1;
    }
}
