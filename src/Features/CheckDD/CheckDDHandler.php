<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDD;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class CheckDDHandler
{
    public static function handle(PhpFileDescriptor $file, $function, $line)
    {
        $dd = Color::yellow($function);
        ErrorPrinter::singleton()->simplePendError(
            self::getLine($file, $function, $dd, $line),
            $file,
            $line,
            'ddFound',
            "Debug function found: '$dd'"
        );
    }

    private static function getTrimmed(PhpFileDescriptor $file, $line): string
    {
        return Str::limit(trim($file->getLine($line)), ErrorPrinter::$terminalWidth);
    }

    private static function getLine(PhpFileDescriptor $file, $function, $dd, $line)
    {
        return Color::gray($line.'| ').str_replace($function, $dd, self::getTrimmed($file, $line));
    }
}
