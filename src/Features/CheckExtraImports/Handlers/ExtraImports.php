<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class ExtraImports
{
    public static function handle($extraImports, $file)
    {
        $printer = ErrorPrinter::singleton();

        foreach ($extraImports as [$class, $lineNumber]) {
            $printer->simplePendError(
                $class,
                $file,
                $lineNumber,
                'extraImports',
                'Extra Import:'
            );
        }
    }
}
