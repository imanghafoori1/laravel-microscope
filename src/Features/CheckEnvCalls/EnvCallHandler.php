<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEnvCalls;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class EnvCallHandler
{
    public static function handle(PhpFileDescriptor $file, $name, $line)
    {
        ErrorPrinter::singleton()->simplePendError(
            self::lineContent($line, $name, $file),
            $file,
            $line,
            'envFound',
            'env() function found: '
        );
    }

    private static function lineContent($line, $name, PhpFileDescriptor $file): string
    {
        return Color::gray($line.'| ').str_replace($name, Color::yellow($name), self::trimmedLined($file, $line));
    }

    private static function trimmedLined(PhpFileDescriptor $file, $line): string
    {
        return Str::limit(trim($file->getLine($line)), ErrorPrinter::$terminalWidth);
    }
}
