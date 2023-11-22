<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class PrintWrongClassRefs
{
    public static function handle(array $wrongClassRefs, $absFilePath)
    {
        $printer = ErrorPrinter::singleton();

        foreach ($wrongClassRefs as $classReference) {
            $wrongClassRef = $classReference['class'];
            $line = $classReference['line'];

            self::wrongRef($printer, $wrongClassRef, $absFilePath, $line);
        }
    }

    private static function wrongRef($printer, $wrongClassRef, $absFilePath, $line): void
    {
        $printer->simplePendError($wrongClassRef, $absFilePath, $line, 'wrongReference', 'Inline class Ref does not exist:');
    }
}
