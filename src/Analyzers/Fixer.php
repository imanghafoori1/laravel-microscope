<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Imanghafoori\Filesystem\FileManipulator;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Psr4\NamespaceCorrector;
use Imanghafoori\SearchReplace\Searcher;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class Fixer
{
    private static function guessCorrect($classBaseName)
    {
        return ForPsr4LoadedClasses::classList()[$classBaseName] ?? [];
    }

    public static function fixReference($absPath, $inlinedClassRef, $lineNum)
    {
        if (config('microscope.no_fix')) {
            return [false, []];
        }

        $classBaseName = class_basename($inlinedClassRef);

        $correct = self::guessCorrect($classBaseName);

        if (\count($correct) !== 1) {
            return [false, $correct];
        }
        $fullClassPath = $correct[0];

        $contextClassNamespace = NamespaceCorrector::getNamespacedClassFromPath($absPath);

        if (NamespaceCorrector::haveSameNamespace($contextClassNamespace, $fullClassPath)) {
            return [self::doReplacement($absPath, $inlinedClassRef, class_basename($fullClassPath), $lineNum), $correct];
        }

        $uses = ParseUseStatement::parseUseStatements(token_get_all(file_get_contents($absPath)))[1];

        // if there is some use statements at the top but the class is not imported.
        if (! count($uses) || isset($uses[$classBaseName])) {
            isset($uses[$classBaseName]) && ($fullClassPath = $classBaseName);

            return [self::doReplacement($absPath, $inlinedClassRef, $fullClassPath, $lineNum), $correct];
        }

        // replace in the class reference
        self::doReplacement($absPath, $inlinedClassRef, $classBaseName, $lineNum);

        // insert a new import at the top
        $lineNum = array_values($uses)[0][1]; // first use statement

        return [FileManipulator::insertNewLine($absPath, "use $fullClassPath;", $lineNum), $correct];
    }

    public static function fixImport($absPath, $import, $lineNum, $isAliased)
    {
        if (config('microscope.no_fix')) {
            return [false, []];
        }

        $correct = self::guessCorrect(class_basename($import));

        if (\count($correct) !== 1) {
            return [false, $correct];
        }

        $tokens = token_get_all(file_get_contents($absPath));
        $hostNamespacedClass = NamespaceCorrector::getNamespacedClassFromPath($absPath);
        // We just remove the wrong import if it is not needed.
        if (! $isAliased && NamespaceCorrector::haveSameNamespace($hostNamespacedClass, $correct[0])) {
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

    private static function doReplacement($absPath, $inlinedClassRef, $classBaseName, $lineNum)
    {
        if (version_compare(PHP_VERSION, '8.0.0') === 1) {
            return FileManipulator::replaceFirst($absPath, $inlinedClassRef, $classBaseName, $lineNum);
        }

        $tokens = token_get_all(file_get_contents($absPath));
        [$newVersion, $lines] = Searcher::searchReplace([
            'fix' => [
                'search' => $inlinedClassRef,
                'replace' => $classBaseName,
            ],
        ], $tokens);
        Filesystem::$fileSystem::file_put_contents($absPath, $newVersion);

        return (bool) $lines;
    }
}
