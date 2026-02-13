<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class WrongClassRefHandler
{
    use GetLineContent;

    public static function handle($wrongClassRef, PhpFileDescriptor $file, int $line): void
    {
        $wrongClassRef = Color::yellow(class_basename($wrongClassRef));
        ErrorPrinter::singleton()->simplePendError(
            self::readLine($line, $file),
            $file,
            $line,
            'wrongClassRef',
            "Inline class ref '$wrongClassRef' does not exist:"
        );
    }
}
