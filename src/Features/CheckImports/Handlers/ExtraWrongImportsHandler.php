<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;

class ExtraWrongImportsHandler
{
    public static $count = 0;

    public static function handle($extraWrongImports, $file)
    {
        $printer = ErrorPrinter::singleton();

        foreach ($extraWrongImports as [$class, $line]) {
            self::$count++;
            $printer->simplePendError(
                Color::gray("$line| ").trim($file->getLine($line), PHP_EOL),
                $file,
                $line,
                'extraImports',
                'Unused & wrong import:',
                '',
                '',
                $class.$line
            );
        }
    }
}
