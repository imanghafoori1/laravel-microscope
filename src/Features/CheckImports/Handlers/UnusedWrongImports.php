<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\ImportsAnalyzer;

class UnusedWrongImports
{
    public static function handle($unusedWrongImports, $absFilePath)
    {
        foreach ($unusedWrongImports as $class) {
            ImportsAnalyzer::$wrongImportsCount++;
            self::wrongImport($absFilePath, $class[0], $class[1]);
        }
    }

    public static function wrongImport($absPath, $class, $lineNumber)
    {
        app(ErrorPrinter::class)->simplePendError(
            "use $class;",
            $absPath,
            $lineNumber,
            'wrongImport',
            'Wrong import:'
        );
    }
}
