<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckBladeQueries;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class BladeQueryHandler
{
    public static function handle(PhpFileDescriptor $file, $class, $lineNumber)
    {
        $class = Color::blue($class);
        ErrorPrinter::singleton()->simplePendError(
            "$class  <=== DB query in blade file",
            $file,
            $lineNumber,
            'queryInBlade',
            'Query in blade file: '
        );
    }
}
