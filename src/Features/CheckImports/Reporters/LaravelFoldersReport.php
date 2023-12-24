<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

class LaravelFoldersReport
{
    use Reporting;

    /**
     * @param  array<string, array<string, array<string, array<int, string>>>>  $foldersStats
     * @return string
     */
    public static function foldersStats($foldersStats)
    {
        $output = '';

        foreach ($foldersStats as $fileType => $stats) {
            [$total, $sub] = self::subDirs($stats);

            $total && ($output .= self::blue($total).$fileType.$sub);

            $output .= PHP_EOL;
        }

        return trim($output, PHP_EOL);
    }

    private static function subDirs($stats): array
    {
        $total = 0;
        $sub = '';
        foreach ($stats as $dir => $filesCount) {
            $total += $filesCount;
            $filesCount && ($sub .= self::addLine($dir, $filesCount));
        }

        return [$total, $sub];
    }
}
