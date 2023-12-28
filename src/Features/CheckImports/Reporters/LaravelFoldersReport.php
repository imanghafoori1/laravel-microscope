<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

class LaravelFoldersReport
{
    use Reporting;

    /**
     * @param  \Generator  $foldersStats
     * @return string
     */
    public static function foldersStats($foldersStats)
    {
        $output = '';

        foreach ($foldersStats as $fileType => $stats) {
            [$total, $sub, $c] = self::subDirs($stats);
            if ($total) {
                $c === 1 && $total = '';

                $output .= self::blue($total).$fileType.$sub;
                $output .= PHP_EOL;
            }
        }

        return trim($output, PHP_EOL);
    }

    /**
     * @param  \Generator  $stats
     * @return array
     */
    private static function subDirs($stats)
    {
        $c = $total = 0;
        $sub = '';
        foreach ($stats as $dir => $files) {
            $c++;
            $filesCount = 0;
            foreach ($files as $_file) {
                $filesCount++;
            }

            $total += $filesCount;
            $filesCount && ($sub .= self::addLine($dir, $filesCount));
        }

        return [$total, $sub, $c];
    }
}
