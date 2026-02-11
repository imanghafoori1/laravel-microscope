<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class ExtraImportsHandler
{
    public static $count = 0;

    public static function handle($extraImports, PhpFileDescriptor $file)
    {
        $printer = ErrorPrinter::singleton();

        foreach ($extraImports as [$class, $lineNumber]) {
            self::$count++;
            $printer->simplePendError(
                Color::gray("$lineNumber| ").trim($file->getLine($lineNumber), PHP_EOL),
                $file,
                $lineNumber,
                'extraImports',
                'Extra Import: '.Color::yellow(class_basename($class)),
                '',
                '',
                $class.$lineNumber
            );
        }
    }
}
