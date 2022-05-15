<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Error;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\TokenAnalyzer\ClassReferenceFinder;
use Imanghafoori\TokenAnalyzer\ClassRefExpander;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class CheckClassReferences
{
    public static $refCount = 0;

    public static function check($tokens, $absPath)
    {
        $imports = ParseUseStatement::parseUseStatements($tokens);
        $imports = $imports[0] ?: [$imports[1]];
        [$classes, $namespace] = ClassReferenceFinder::process($tokens);
        $unusedRefs = ParseUseStatement::getUnusedImports($classes, $imports, []);
        [$classes,] = ClassRefExpander::expendReferences($classes, $imports, $namespace);

        $printer = app(ErrorPrinter::class);

        $wrongImports = [];
        foreach ($classes as $class) {
            self::$refCount++;
            if (! self::exists($class['class'])) {
                $wrongImports[] = $class['class'];
                $printer->wrongUsedClassError($absPath, $class['class'], $class['line']);
            }
        }

        foreach ($unusedRefs as $class) {
            self::$refCount++;
            if (! in_array($class[0], $wrongImports)) {
                $printer->extraImport($absPath, $class[0], $class[1]);
            }
        }
    }

    private static function exists($class)
    {
        try {
            return class_exists($class) || interface_exists($class) || function_exists($class);
        } catch (Error $e) {
            app(ErrorPrinter::class)->simplePendError($e->getMessage(), $e->getFile(), $e->getLine(), 'error', 'File error');

            return true;
        }
    }
}
