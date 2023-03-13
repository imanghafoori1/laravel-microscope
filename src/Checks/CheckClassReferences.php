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

    public static $unusedImportsCount = 0;

    public static $wrongImportsCount = 0;

    public static $wrongClassRefCount = 0;

    public static function check($tokens, $absPath)
    {
        [$wrongImports, $unusedImports, $allRefsCount] = self::getBadImports($tokens);

        self::$unusedImportsCount += count($unusedImports);
        self::$refCount += $allRefsCount;
        self::$wrongImportsCount += count($wrongImports);

        /**
         * @var $printer  ErrorPrinter
         */
        $printer = app(ErrorPrinter::class);

        foreach ($wrongImports as $class) {
            $printer->wrongUsedClassError($absPath, $class['class'], $class['line']);
        }

        foreach ($unusedImports as $class) {
            $printer->extraImport($absPath, $class[0], $class[1]);
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

    private static function getUnusedCorrectImports($unusedRefs, $wrongImports)
    {
        $wrongs = [];
        foreach ($wrongImports as $import) {
            $wrongs[$import['class']] = 1;
        }

        $unusedCorrectImports = [];

        foreach ($unusedRefs as $class) {
            // if is not a wrong import:
            if (! isset($wrongs[$class[0]])) {
                $unusedCorrectImports[] = $class;
            }
        }

        return $unusedCorrectImports;
    }

    private static function getWrongClassRefs($expandedClasses)
    {
        $wrongImports = [];
        foreach ($expandedClasses as $class) {
            if (! self::exists($class['class'])) {
                $wrongImports[] = $class;
            }
        }

        return $wrongImports;
    }

    private static function getBadImports($tokens)
    {
        $imports = ParseUseStatement::parseUseStatements($tokens);
        $imports = $imports[0] ?: [$imports[1]];
        [$classes, $namespace] = ClassReferenceFinder::process($tokens);
        [$expandedClasses] = ClassRefExpander::expendReferences($classes, $imports, $namespace);

        $wrongImports = self::getWrongClassRefs($expandedClasses);
        $unusedCorrectImports = self::getUnusedCorrectImports(
            ParseUseStatement::getUnusedImports($classes, $imports, []),
            $wrongImports
        );

        return [
            $wrongImports,
            $unusedCorrectImports,
            count($expandedClasses),
        ];
    }
}
