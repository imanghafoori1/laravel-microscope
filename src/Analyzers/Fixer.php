<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Refactor\PatternParser;

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
            return [FileManipulator::replaceFirst($absPath, $inlinedClassRef, class_basename($fullClassPath), $lineNum), $correct];
        }

        $uses = ParseUseStatement::parseUseStatements(token_get_all(file_get_contents($absPath)))[1];

        // if there is any use statement at the top
        if (count($uses) && ! isset($uses[$classBaseName])) {
            // replace in the class reference
            FileManipulator::replaceFirst($absPath, $inlinedClassRef, $classBaseName, $lineNum);

            // insert a new import at the top
            $lineNum = array_values($uses)[0][1]; // first use statement

            return [FileManipulator::insertAtLine($absPath, "use $fullClassPath;", $lineNum), $correct];
        }

        isset($uses[$classBaseName]) && ($fullClassPath = $classBaseName);

        return [FileManipulator::replaceFirst($absPath, $inlinedClassRef, $fullClassPath, $lineNum), $correct];
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
            return [self::replaceSave($import, '', $tokens, $absPath), [' Deleted!']];
        }

        return [self::replaceSave($import, 'use '.$correct[0].';', $tokens, $absPath), $correct];
    }

    private static function replaceSave($old, $new, array $tokens, $absPath)
    {
        [$newVersion, $lines] = PatternParser::searchReplace(['use '.$old.';' => $new], $tokens);
        file_put_contents($absPath, $newVersion);

        return $lines;
    }
}
