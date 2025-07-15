<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

class LaravelFoldersReport
{
    use Reporting;

    /**
     * @param  iterable<string, iterable<string, iterable<string, iterable<int, string>>>>  $foldersStats
     * @return string
     */
    public static function foldersStats($foldersStats): string
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
     * @param  iterable<string, iterable<string, iterable<int, string>>>  $stats
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
