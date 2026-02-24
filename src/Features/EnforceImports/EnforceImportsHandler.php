<?php

namespace Imanghafoori\LaravelMicroscope\Features\EnforceImports;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class EnforceImportsHandler
{
    public static $noFix;

    public static function handle($classRef, $file, $line)
    {
        if (self::$noFix) {
            $header = 'FQCN needs to be imported';
        } else {
            $header = 'FQCN got imported at the top';
        }

        ErrorPrinter::singleton()->simplePendError($classRef, $file, $line, 'enforce_imports', $header);
    }
}
