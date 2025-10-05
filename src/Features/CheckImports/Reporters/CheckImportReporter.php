<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

use Imanghafoori\LaravelMicroscope\ErrorReporters\Reporting;
use JetBrains\PhpStorm\Pure;

class CheckImportReporter
{
    use Reporting;

    #[Pure]
    public static function totalImportsMsg()
    {
        return '<options=bold;fg=yellow>Imports were checked under:</>';
    }

    #[Pure]
    public static function getFilesStats($count)
    {
        return self::blue($count).'class'.($count <= 1 ? '' : 'es');
    }

    #[Pure]
    public static function header(): string
    {
        return ' ⬛️ <fg=blue>Overall:</>';
    }
}
