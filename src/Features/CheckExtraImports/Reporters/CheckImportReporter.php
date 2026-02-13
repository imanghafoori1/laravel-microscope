<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Reporters;

use Imanghafoori\LaravelMicroscope\ErrorReporters\Reporting;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use JetBrains\PhpStorm\Pure;

class CheckImportReporter
{
    use Reporting;

    #[Pure]
    public static function totalImportsMsg()
    {
        return Color::boldYellow('Imports were checked under:');
    }

    #[Pure]
    public static function header()
    {
        return [' ⬛️ '.Color::blue('Overall:'), PHP_EOL];
    }
}
