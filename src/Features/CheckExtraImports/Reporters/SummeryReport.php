<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Reporters;

use Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Handlers\ExtraImportsHandler;
use Imanghafoori\LaravelMicroscope\Foundations\Color;

class SummeryReport
{
    public static function summery($importCount)
    {
        return [
            PHP_EOL,
            self::formatErrorSummary($importCount),
            PHP_EOL,
            self::unusedImportsCount(),
        ];
    }

    public static function formatErrorSummary($allImportsCount)
    {
        return Color::boldYellow("$allImportsCount imports were checked.");
    }

    public static function unusedImportsCount()
    {
        $count = ExtraImportsHandler::$count;
        $s = $count === 1 ? '' : 's';
        $count = Color::yellow($count);

        return " 🔸 $count unused import$s found.";
    }

    public static function noImportsFound($filter)
    {
        return Color::boldYellow('No imports were found!').' with filter: "'.Color::red($filter).'"';
    }
}
