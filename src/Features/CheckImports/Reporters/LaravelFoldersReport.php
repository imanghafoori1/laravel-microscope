<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

class LaravelFoldersReport
{
    use Reporting;

    /**
     * @param  iterable<string, iterable<string, iterable<string, iterable<int, string>>>>  $foldersStats
     * @return \Generator<int, string>
     */
    public static function foldersStats($foldersStats)
    {
        foreach ($foldersStats as $fileType => $stats) {
            [$total, $sub, $c] = self::subDirs($stats);
            if ($total) {
                $c === 1 && $total = '';

                yield self::blue($total).$fileType.$sub.PHP_EOL;
            }
        }
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
