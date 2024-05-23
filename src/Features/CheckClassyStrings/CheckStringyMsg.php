<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckClassyStrings;

use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\Console\Terminal;

class CheckStringyMsg
{
    #[Pure]
    public static function successfulReplacementMsg($classPath)
    {
        return '<fg=green>✔ Replaced with: </><fg=red>'.$classPath.'</>';
    }

    public static function lineSeparator(): string
    {
        return ' <fg='.config('microscope.colors.line_separator').'>'.str_repeat('_', (new Terminal)->getWidth() - 4).'</>';
    }

    #[Pure]
    public static function question($class)
    {
        return 'Replace: <fg=blue>'.$class.'</> with <fg=blue>::class</> version of it?';
    }

    public static function getLineContents($lineNumber, PhpFileDescriptor $file)
    {
        return $lineNumber.' |'.$file->getLine($lineNumber);
    }

    public static function finished()
    {
        return ' <fg='.config('microscope.colors.line_separator').'> ✔ - Finished looking for stringy classes.</>';
    }
}
