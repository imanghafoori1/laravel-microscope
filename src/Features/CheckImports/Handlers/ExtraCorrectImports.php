<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class ExtraCorrectImports
{
    public static function handle($extraCorrectImports, $file)
    {
        $printer = ErrorPrinter::singleton();

        foreach ($extraCorrectImports as [$class, $lineNumber]) {
            $printer->simplePendError(
                $class,
                $file,
                $lineNumber,
                'extraCorrectImport',
                'Extra Import:'
            );
        }
    }
}
