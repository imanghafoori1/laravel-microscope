<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Analyzers\Fixer;
use Imanghafoori\LaravelMicroscope\Analyzers\ImportsAnalyzer;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckClassReferencesAreValid
{
    public static $checkWrong = true;

    public static $checkUnused = true;

    public static function check($tokens, $absFilePath, $params = [])
    {
        event('laravel_microscope.checking_file', [$absFilePath]);

        return self::checkAndHandleClassRefs($tokens, $absFilePath, $params);
    }

    private static function fixClassReference($absFilePath, $class, $line, $namespace)
    {
        $baseClassName = Str::replaceFirst($namespace.'\\', '', $class);

        // Imports the correct namespace:
        [$wasCorrected, $corrections] = Fixer::fixReference($absFilePath, $baseClassName, $line);

        if ($wasCorrected) {
            return [$wasCorrected, $corrections];
        }

        return Fixer::fixReference($absFilePath, $class, $line);
    }

    private static function checkAndHandleClassRefs($tokens, $absFilePath, $imports)
    {
        $printer = app(ErrorPrinter::class);

        loopStart:
        [
            $hostNamespace,
            $unusedWrongImports,
            $unusedCorrectImports,
            $wrongClassRefs,
            $wrongDocblockRefs,
        ] = ImportsAnalyzer::getWrongRefs($tokens, $absFilePath, $imports);

        if (self::$checkWrong) {
            [$tokens, $isFixed] = self::handleWrongClassRefs(
                array_merge($wrongClassRefs, $wrongDocblockRefs),
                $absFilePath,
                $hostNamespace,
                $tokens
            );

            if ($isFixed) {
                goto loopStart;
            }

            foreach ($unusedWrongImports as $class) {
                ImportsAnalyzer::$wrongImportsCount++;
                //$isCorrected = self::tryToFix($classImport, $absFilePath, $line, $as, $printer);
                $printer->wrongImport($absFilePath, $class[0], $class[1]);
            }
        }

        if (self::$checkUnused) {
            self::handleUnusedImports($unusedCorrectImports, $absFilePath);
        }

        return $tokens;
    }

    public static function isInUserSpace($class): bool
    {
        $isInUserSpace = false;
        $class = ltrim($class, '\\');
        foreach (ComposerJson::readAutoload() as $autoload) {
            if (Str::startsWith($class, \array_keys($autoload))) {
                $isInUserSpace = true;
            }
        }

        return $isInUserSpace;
    }

    private static function handleUnusedImports($unusedCorrectImports, $absFilePath)
    {
        $printer = app(ErrorPrinter::class);

        foreach ($unusedCorrectImports as $class) {
            ImportsAnalyzer::$refCount++;
            ImportsAnalyzer::$unusedImportsCount++;
            $printer->extraImport($absFilePath, $class[0], $class[1]);
        }
    }

    private static function handleWrongClassRefs(array $wrongClassRefs, $absFilePath, $hostNamespace, array $tokens): array
    {
        $printer = app(ErrorPrinter::class);

        foreach ($wrongClassRefs as $classReference) {
            $wrongClassRef = $classReference['class'];
            $line = $classReference['line'];

            if (! self::isInUserSpace($wrongClassRef)) {
                $printer->doesNotExist($wrongClassRef, $absFilePath, $line, 'wrongReference', 'Inline class Ref does not exist:');
                continue;
            }

            $beforeFix = file_get_contents($absFilePath);
            [, $corrections] = self::fixClassReference($absFilePath, $wrongClassRef, $line, $hostNamespace);
            // To make sure that the file is really changed,
            // and we do not end up in an infinite loop.
            $afterFix = file_get_contents($absFilePath);
            $isFixed = $beforeFix !== $afterFix;

            // print
            $method = $isFixed ? 'printFixation' : 'wrongImportPossibleFixes';
            $printer->$method($absFilePath, $wrongClassRef, $line, $corrections);

            if ($isFixed) {
                $tokens = token_get_all($afterFix);

                return [$tokens, $isFixed];
            }
        }

        return [$tokens, false];
    }
}
