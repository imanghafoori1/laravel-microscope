<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckView\Check;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckViewHandler
{
    public static function handle($file, $lineNumber, $fileName)
    {
        ErrorPrinter::singleton()->simplePendError(
            $fileName.'.blade.php',
            $file,
            $lineNumber,
            'missing_view',
            'The blade file is missing:',
            ' does not exist'
        );
    }
}
