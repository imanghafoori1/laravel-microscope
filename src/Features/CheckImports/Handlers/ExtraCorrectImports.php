<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\ImportsAnalyzer;

class ExtraCorrectImports
{
    public static function handle($unusedCorrectImports, $absFilePath)
    {
        foreach ($unusedCorrectImports as $class) {
            ImportsAnalyzer::$refCount++;
            ImportsAnalyzer::$unusedImportsCount++;
            self::extraImport($absFilePath, $class[0], $class[1]);
        }
    }

    public static function extraImport($absPath, $class, $lineNumber)
    {
        ErrorPrinter::singleton()->simplePendError(
            $class,
            $absPath,
            $lineNumber,
            'extraImport',
            'Extra Import:'
        );
    }
}
