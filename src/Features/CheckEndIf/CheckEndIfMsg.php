<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEndIf;

use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class CheckEndIfMsg
{
    public static function confirm(PhpFileDescriptor $file)
    {
        return 'Replacing endif in: '.Color::blue($file->relativePath());
    }
}