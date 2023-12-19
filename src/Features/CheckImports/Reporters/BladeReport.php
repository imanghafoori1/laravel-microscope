<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

class BladeReport
{
    use Reporting;

    /**
     * @param array<string, int> $stats
     * @param int $filesCount
     * @return string
     */
    public static function getBladeStats($stats, $filesCount): string
    {
        $output = self::blue($filesCount).'blade'.($filesCount === 0 ? '' : 's');
        foreach ($stats as $path => $count) {
            $count && ($output .= self::addLine($path, $count));
        }

        return $output;
    }
}