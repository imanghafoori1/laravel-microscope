<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\ErrorCounter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class SummeryReport
{
    public static function summery($errorsList)
    {
        ErrorCounter::calculateErrors($errorsList);

        $messages = [
            self::formatErrorSummary(ErrorCounter::getTotalErrors(), ImportsAnalyzer::$checkedRefCount),
            self::format('wrong import', ErrorCounter::getExtraWrongCount()),
            self::format('wrong class reference', ErrorCounter::getWrongUsedClassCount()),
        ];

        return implode(PHP_EOL, $messages);
    }

    public static function formatErrorSummary($totalCount, $checkedRefCount)
    {
        return Color::boldYellow($checkedRefCount.' references were checked, '.$totalCount.' error'.($totalCount === 1 ? '' : 's').' found.');
    }

    public static function format($errorType, $count)
    {
        $int = Color::yellow($count);
        $s = $int === 1 ? '' : 's';

        return " 🔸 $int $errorType{$s} found.";
    }

    public static function noImportsFound($filter)
    {
        return Color::boldYellow('No imports were found!').' with filter: "'.Color::red($filter).'"';
    }
}
