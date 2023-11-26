<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use ImanGhafoori\ComposerJson\NamespaceCalculator;
use Imanghafoori\Filesystem\FileManipulator;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\ClassListProvider;
use Imanghafoori\SearchReplace\Searcher;
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
        foreach (ComposerJson::readAutoload() as $autoload) {
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

        $contextClassNamespace = ComposerJson::make()->getNamespacedClassFromPath($absPath);

        if (NamespaceCalculator::haveSameNamespace($contextClassNamespace, $fullClassPath)) {
            return [self::doReplacement($absPath, $inlinedClassRef, class_basename($fullClassPath), $lineNum), $correct];
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

            return [self::doReplacement($absPath, $inlinedClassRef, $fullClassPath, $lineNum), $correct];
        }

        // Replace in the class reference
        self::doReplacement($absPath, $inlinedClassRef, $classBaseName, $lineNum);

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

        if (\count($correct) !== 1) {
            return [false, $correct];
        }

        $tokens = token_get_all(file_get_contents($absPath));
        $hostNamespacedClass = ComposerJson::make()->getNamespacedClassFromPath($absPath);
        // We just remove the wrong import if it is not needed.
        if (! $isAliased && NamespaceCalculator::haveSameNamespace($hostNamespacedClass, $correct[0])) {
            return [self::replaceSave("use $import;", '', $tokens, $absPath), [' Deleted!']];
        }

        return [self::replaceSave("use $import;", 'use '.$correct[0].';'.PHP_EOL, $tokens, $absPath), $correct];
    }

    private static function replaceSave($old, $new, array $tokens, $absPath)
    {
        [$newVersion, $lines] = Searcher::searchReplace([
            'fix' => [
                'search' => $old,
                'replace' => $new,
            ],
        ], $tokens);

        Filesystem::$fileSystem::file_put_contents($absPath, $newVersion);

        return $lines;
    }

    private static function doReplacement($absPath, $wrongRef, $correctRef, $lineNum)
    {
        if (version_compare(PHP_VERSION, '8.0.0') === 1) {
            return FileManipulator::replaceFirst($absPath, $wrongRef, $correctRef, $lineNum);
        }

        $tokens = token_get_all(file_get_contents($absPath));
        [$newVersion, $lines] = Searcher::searchReplace([
            'fix' => [
                'search' => $wrongRef,
                'replace' => $correctRef,
            ],
        ], $tokens);
        Filesystem::$fileSystem::file_put_contents($absPath, $newVersion);

        return (bool) $lines;
    }
}
