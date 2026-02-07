<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\ErrorCounter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class SummeryReport
{
    public static function summery(ErrorCounter $counter)
    {
        return [
            PHP_EOL,
            self::formatErrorSummary($counter->getTotalErrors(), ImportsAnalyzer::$checkedRefCount),
            PHP_EOL,
            self::format('wrong import', $counter->getExtraWrongCount()),
            PHP_EOL,
            self::format('wrong class reference', $counter->getWrongUsedClassCount()),
        ];
    }

    public static function formatErrorSummary($totalCount, $checkedRefCount)
    {
        $s = $totalCount === 1 ? '' : 's';

        return Color::boldYellow("$checkedRefCount references were checked, $totalCount error$s found.");
    }

    public static function format($errorType, $count)
    {
        $s = $count === 1 ? '' : 's';
        $int = Color::yellow($count);

        return " 🔸 $int $errorType{$s} found.";
    }

    public static function noImportsFound($filter)
    {
        return Color::boldYellow('No imports were found!').' with filter: "'.Color::red($filter).'"';
    }
}
