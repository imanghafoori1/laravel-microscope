<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Reporters;

use Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Checks\CheckImportsAreUsed;
use Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\ErrorCounter;

class SummeryReport
{
    public static function summery($errorsList)
    {
        ErrorCounter::calculateErrors($errorsList);

        $messages = [
            self::formatErrorSummary(
                CheckImportsAreUsed::$importsCount,
                ErrorCounter::getTotalErrors()
            ),
            self::format('unused import', ErrorCounter::getExtraImportsCount()),
        ];

        return implode(PHP_EOL, $messages);
    }

    public static function formatErrorSummary($totalCount, $checkedRefCount)
    {
        return '<options=bold;fg=yellow>'.$checkedRefCount.' references were checked, '.$totalCount.' error'.($totalCount == 1 ? '' : 's').' found.</>';
    }

    public static function format($errorType, $count)
    {
        return ' 🔸 <fg=yellow>'.$count.'</> '.$errorType.($count == 1 ? '' : 's').' found.';
    }
}
