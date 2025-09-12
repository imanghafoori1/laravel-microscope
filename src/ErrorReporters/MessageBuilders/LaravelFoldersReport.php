<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders;

use Imanghafoori\LaravelMicroscope\ErrorReporters\Reporting;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;

class LaravelFoldersReport
{
    use Reporting;

    /**
     * @param  array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto>  $foldersStats
     * @return \Generator<int, string>
     */
    public static function formatFoldersStats($foldersStats)
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
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto  $stats
     * @return array
     */
    private static function subDirs($stats)
    {
        $c = $total = 0;
        $sub = '';
        foreach ($stats->stats as $dir => $files) {
            $c++;
            // consume generator:
            $filesCount = Loop::countAll($files->files);

            $total += $filesCount;
            $filesCount && ($sub .= self::addLine($dir, $filesCount));
        }

        return [$total, $sub, $c];
    }
}
