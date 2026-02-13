<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

trait GetLineContent
{
    public static function readLine(int $line, PhpFileDescriptor $file): string
    {
        return Color::gray("$line| ").trim($file->getLine($line), PHP_EOL.' ');
    }
}
