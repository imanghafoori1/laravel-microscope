<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Imanghafoori\LaravelMicroscope\Analyzers\ImportsAnalyzer;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckClassReferences
{
    public static $refCount = 0;

    public static $unusedImportsCount = 0;

    public static $wrongImportsCount = 0;

    public static function check($tokens, $absPath)
    {
        [$wrongClassRefs, $unusedCorrectImports, $allRefsCount] = ImportsAnalyzer::getBadClassRefs($tokens, $absPath);

        self::$unusedImportsCount += count($unusedCorrectImports);
        self::$refCount += $allRefsCount;
        self::$wrongImportsCount += count($wrongClassRefs);

        /**
         * @var $printer  ErrorPrinter
         */
        $printer = app(ErrorPrinter::class);

        foreach ($wrongClassRefs as $class) {
            $printer->wrongUsedClassError($absPath, $class['class'], $class['line']);
        }

        foreach ($unusedCorrectImports as $class) {
            $printer->extraImport($absPath, $class[0], $class[1]);
        }
    }
}
