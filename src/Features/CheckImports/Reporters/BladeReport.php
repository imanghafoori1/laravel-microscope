<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

use Imanghafoori\LaravelMicroscope\ErrorReporters\Reporting;
use JetBrains\PhpStorm\Pure;

class BladeReport
{
    use Reporting;

    /**
     * @param  array<string, \Generator<string, int>>  $stats
     * @return string
     */
    #[Pure]
    public static function getBladeStats($stats): string
    {
        $c = $total = 0;
        $output = '';
        foreach ($stats as $stat) {
            foreach ($stat as $path => $count) {
                $c++;
                $total += $count;
                $output .= self::addLine($path, $count);
            }
        }
        if (! $total) {
            return '';
        }

        $c === 1 && $total = '';

        return self::blue($total).'blade'.($total > 1 ? 's' : '').$output;
    }
}
