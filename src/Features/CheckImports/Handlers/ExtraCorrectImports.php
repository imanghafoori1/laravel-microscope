<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class ExtraCorrectImports
{
    public static function handle($extraCorrectImports, $absFilePath)
    {
        $printer = ErrorPrinter::singleton();

        foreach ($extraCorrectImports as [$class, $lineNumber]) {
            $printer->simplePendError(
                $class,
                $absFilePath,
                $lineNumber,
                'extraCorrectImport',
                'Extra Import:'
            );
        }
    }
}
