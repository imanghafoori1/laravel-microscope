<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraFQCN;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class ExtraFqcnHandler
{
    public static function reportAliasImported($file, $alias, $classRef)
    {
        $header = 'FQCN is already imported with an alias: '.$alias;
        $body = $classRef['class'].' can be replaced with: '.$alias;

        ErrorPrinter::singleton()->simplePendError(
            $body, $file, $classRef['line'], 'FQCN', $header
        );
    }

    public static function reportSameNamespace($classRef, $file, $fix)
    {
        $header = 'FQCN is already on the same namespace.';
        $fix && ($header .= ' (fixed)');

        ErrorPrinter::singleton()->simplePendError(
            $classRef['class'], $file, $classRef['line'], 'FQCN', $header
        );
    }

    public static function reportAlreadyImported(array $classRef, $file, $line)
    {
        $header = 'FQCN is already imported at line: '.$line;

        ErrorPrinter::singleton()->simplePendError(
            $classRef['class'], $file, $classRef['line'], 'FQCN', $header
        );
    }
}
