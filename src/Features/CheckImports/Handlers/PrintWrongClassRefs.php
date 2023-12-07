<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class PrintWrongClassRefs
{
    public static function handle(array $wrongClassRefs, $absFilePath)
    {
        $printer = ErrorPrinter::singleton();

        foreach ($wrongClassRefs as $classReference) {
            $printer->simplePendError(
                $classReference['class'],
                $absFilePath,
                $classReference['line'],
                'wrongClassRef',
                'Class Reference does not exist:'
            );
        }
    }
}
