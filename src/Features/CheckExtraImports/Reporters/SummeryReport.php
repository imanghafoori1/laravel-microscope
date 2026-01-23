<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Reporters;

use Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Checks\CheckImportsAreUsed;
use Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\ErrorCounter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;

class SummeryReport
{
    public static function summery($errorsList)
    {
        ErrorCounter::calculateErrors($errorsList);

        $messages = [
            self::formatErrorSummary(
                ErrorCounter::getTotalErrors(),
                CheckImportsAreUsed::$importsCount
            ),
            self::format('unused import', ErrorCounter::getExtraImportsCount()),
        ];

        return implode(PHP_EOL, $messages);
    }

    public static function formatErrorSummary($totalCount, $checkedRefCount)
    {
        return Color::boldYellow($checkedRefCount.' references were checked, '.$totalCount.' error'.($totalCount === 1 ? '' : 's').' found.');
    }

    public static function format($errorType, $count)
    {
        return ' 🔸 '.Color::yellow($count).' '.$errorType.($count === 1 ? '' : 's').' found.';
    }

    public static function noImportsFound($filter)
    {
        return Color::boldYellow('No imports were found!').' with filter: "'.Color::red($filter).'"';
    }
}
