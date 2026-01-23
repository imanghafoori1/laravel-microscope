<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;

class PrintWrongClassRefs
{
    public static function handle(array $wrongClassRefs, $absFilePath)
    {
        $printer = ErrorPrinter::singleton();

        Loop::over($wrongClassRefs, fn ($classRef) => $printer->simplePendError(
            $classRef['class'],
            $absFilePath,
            $classRef['line'],
            'wrongClassRef',
            'Class Reference does not exist:'
        ));
    }
}
