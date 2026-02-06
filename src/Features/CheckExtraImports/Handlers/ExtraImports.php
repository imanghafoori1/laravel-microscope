<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use JetBrains\PhpStorm\Pure;

class ExtraImports
{
    public static $count = 0;

    public static function handle($extraImports, $file)
    {
        $printer = ErrorPrinter::singleton();

        foreach ($extraImports as [$class, $lineNumber]) {
            self::$count++;
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
