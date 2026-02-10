<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckRoutes;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;

class WrongRouteCallHandler
{
    public static function route($path, $errorIt, $errorTxt, $absPath = null, $lineNumber = 0)
    {
        ErrorPrinter::singleton()->simplePendError(
            $path,
            $absPath,
            $lineNumber,
            'route',
            $errorIt,
            $errorTxt
        );
    }

    public static function printError($file, $lineNumber, $routeName)
    {
        $routeName = Color::blue($routeName);
        ErrorPrinter::singleton()->simplePendError(
            "route($routeName)",
            $file,
            $lineNumber,
            'route',
            'Route name does not exist: ',
            '  <=== is wrong',
        );
    }
}