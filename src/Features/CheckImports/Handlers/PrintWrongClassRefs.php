<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;

class PrintWrongClassRefs
{
    public static function handle(array $wrongClassRefs, $file)
    {
        $printer = ErrorPrinter::singleton();

        Loop::over($wrongClassRefs, fn ($classRef) => $printer->simplePendError(
            Color::gray($classRef['line']."| ").trim($file->getLine($classRef['line']), PHP_EOL),
            $file,
            $classRef['line'],
            'wrongClassRef',
            'Class Reference '.$classRef['class'].' does not exist:'
        ));
    }
}
