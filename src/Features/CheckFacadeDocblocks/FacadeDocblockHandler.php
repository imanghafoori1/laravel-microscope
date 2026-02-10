<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckFacadeDocblocks;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class FacadeDocblockHandler
{
    public static $command;

    public static function onError($accessor, $file)
    {
        ErrorPrinter::singleton()->simplePendError(
            '"'.$accessor.'"',
            $file,
            20,
            'key',
            'The Facade Accessor Not Found.'
        );
    }

    public static function onFix($class, $file)
    {
        ErrorPrinter::singleton()->simplePendError(
            ' ➖ Fixed doc-blocks for:',
            $file,
            4,
            'docblocks',
            $class
        );
    }
}