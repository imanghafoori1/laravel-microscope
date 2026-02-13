<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class WrongImportHandler
{
    use GetLineContent;

    public static function handle($class, PhpFileDescriptor $file, int $line)
    {
        ErrorPrinter::singleton()->simplePendError(
            self::readLine($line, $file),
            $file,
            $line,
            'wrongClassRef',
            'Class '.Color::yellow(class_basename($class)).' does not exist:'
        );
    }
}
