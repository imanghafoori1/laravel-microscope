<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckClassyStrings;

use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use JetBrains\PhpStorm\Pure;

class CheckStringyMsg
{
    #[Pure]
    public static function successfulReplacementMsg($classPath)
    {
        return Color::green('✔ Replaced with:').' '.Color::red($classPath);
    }

    #[Pure]
    public static function question($class)
    {
        return 'Replace: '.Color::blue($class).' with '.Color::blue('::class').' version of it?';
    }

    #[Pure(true)]
    public static function getLineContents($lineNumber, PhpFileDescriptor $file)
    {
        return $lineNumber.' |'.$file->getLine($lineNumber);
    }
}
