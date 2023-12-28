<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

class BladeReport
{
    use Reporting;

    /**
     * @param  \Generator  $stats
     * @return string
     */
    public static function getBladeStats($stats): string
    {
        $c = $total = 0;
        $output = '';
        foreach ($stats as $path => $count) {
            if (! $count) {
                continue;
            }
            $c++;
            $total += $count;
            $output .= self::addLine($path, $count);
        }
        if (! $total) {
            return '';
        }

        $c === 1 && $total = '';

        return self::blue($total).'blade'.($total > 1 ? 's' : '').$output;
    }
}
