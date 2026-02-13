<?php

namespace Imanghafoori\LaravelMicroscope\Features\EnforceImports;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class EnforceImportsHandler
{
    public static function handler($noFix)
    {
        if ($noFix) {
            $header = 'FQCN needs to be imported';
        } else {
            $header = 'FQCN got imported at the top';
        }

        return function ($classRef, $file, $line) use ($header) {
            ErrorPrinter::singleton()->simplePendError($classRef, $file, $line, 'enforce_imports', $header);
        };
    }
}
