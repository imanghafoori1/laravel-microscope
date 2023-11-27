<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\ImportsAnalyzer;

class ExtraWrongImports
{
    public static function handle($unusedWrongImports, $absPath)
    {
        foreach ($unusedWrongImports as $class) {
            ImportsAnalyzer::$wrongImportsCount++;
            self::wrongImport($absPath, $class[0], $class[1]);
        }
    }

    public static function wrongImport($absPath, $class, $lineNumber)
    {
        ErrorPrinter::singleton()->simplePendError(
            "use $class;",
            $absPath,
            $lineNumber,
            'wrongImport',
            'Unused & wrong import:'
        );
    }
}
