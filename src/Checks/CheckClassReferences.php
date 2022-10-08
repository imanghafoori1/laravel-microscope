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
        self::$refCount = self::$refCount + count($imports);
        [$classes, $namespace] = ClassReferenceFinder::process($tokens);
        $unusedRefs = ParseUseStatement::getUnusedImports($classes, $imports, []);
        [$expandedClasses,] = ClassRefExpander::expendReferences($classes, $imports, $namespace);

        /**
         * @var $printer  ErrorPrinter
         */
        $printer = app(ErrorPrinter::class);

        $wrongImports = self::printWrongImports($expandedClasses, $printer, $absPath);

        self::printImportNotUsed($unusedRefs, $wrongImports, $printer, $absPath);
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

    private static function printImportNotUsed($unusedRefs, $wrongImports, ErrorPrinter $printer, $absPath)
    {
        foreach ($unusedRefs as $class) {
            if (! in_array($class[0], $wrongImports)) {
                $printer->extraImport($absPath, $class[0], $class[1]);
            }
        }
    }

    private static function printWrongImports($expandedClasses, ErrorPrinter $printer, $absPath): array
    {
        $wrongImports = [];
        foreach ($expandedClasses as $class) {
            if (! self::exists($class['class'])) {
                $wrongImports[] = $class['class'];
                $printer->wrongUsedClassError($absPath, $class['class'], $class['line']);
            }
        }

        return $wrongImports;
    }
}
