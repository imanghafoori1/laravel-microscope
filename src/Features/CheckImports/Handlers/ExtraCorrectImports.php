<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class ExtraCorrectImports
{
    public static function handle($unusedCorrectImports, $absFilePath)
    {
        foreach ($unusedCorrectImports as $class) {
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
