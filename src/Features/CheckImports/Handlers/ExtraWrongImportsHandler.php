<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class ExtraWrongImportsHandler
{
    public static $count = 0;

    public static function handle($extraWrongImports, $file)
    {
        $printer = ErrorPrinter::singleton();

        foreach ($extraWrongImports as [$class, $lineNumber]) {
            self::$count++;
            $printer->simplePendError(
                "use $class;",
                $file,
                $lineNumber,
                'extraImports',
                'Unused & wrong import:',
                '',
                '',
                $class.$lineNumber
            );
        }
    }
}
