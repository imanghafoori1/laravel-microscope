<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class ExtraImports
{
    public static $count = 0;

    public static function handle($extraImports, PhpFileDescriptor $file)
    {
        $printer = ErrorPrinter::singleton();

        foreach ($extraImports as [$class, $line]) {
            self::$count++;
            $printer->simplePendError(
                Color::gray($line.'| ').trim($file->getLine($line), PHP_EOL),
                $file,
                $line,
                'extraImports',
                'Extra Import: '.Color::yellow(class_basename($class))
            );
        }
    }
}
