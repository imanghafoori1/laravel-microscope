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
        $total = 0;
        $output = '';
        foreach ($stats as $path => $count) {
            $total += $count;
            $count && ($output .= self::addLine($path, $count));
        }
        if (! $total) {
            return '';
        }

        return self::blue($total).'blade'.($total ? 's' : '').$output;
    }
}
