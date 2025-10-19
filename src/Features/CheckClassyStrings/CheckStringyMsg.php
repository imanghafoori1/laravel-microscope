<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckClassyStrings;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Foundations\Reports\LineSeperator;
use JetBrains\PhpStorm\Pure;

class CheckStringyMsg
{
    #[Pure]
    public static function successfulReplacementMsg($classPath)
    {
        return '<fg=green>✔ Replaced with: </><fg=red>'.$classPath.'</>';
    }

    #[Pure(true)]
    public static function lineSeparator(): string
    {
        return ' <fg='.LineSeperator::$color.'>'.str_repeat('_', ErrorPrinter::$terminalWidth - 4).'</>';
    }

    #[Pure]
    public static function question($class)
    {
        return 'Replace: <fg=blue>'.$class.'</> with <fg=blue>::class</> version of it?';
    }

    #[Pure(true)]
    public static function getLineContents($lineNumber, PhpFileDescriptor $file)
    {
        return $lineNumber.' |'.$file->getLine($lineNumber);
    }

    #[Pure(true)]
    public static function finished()
    {
        return ' <fg='.LineSeperator::$color.'> ✔ - Finished looking for stringy classes.</>';
    }
}
