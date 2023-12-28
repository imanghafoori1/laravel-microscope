<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\ErrorCounter;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class SummeryReport
{
    public static function summery($errors)
    {
        ErrorCounter::$errors = $errors;

        $messages = [
            self::formatErrorSummary(ErrorCounter::getTotalErrors(), ImportsAnalyzer::$checkedRefCount),
            self::format('unused import', ErrorCounter::getExtraImportsCount()),
            self::format('wrong import', ErrorCounter::getExtraWrongCount()),
            self::format('wrong class reference', ErrorCounter::getWrongUsedClassCount()),
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
