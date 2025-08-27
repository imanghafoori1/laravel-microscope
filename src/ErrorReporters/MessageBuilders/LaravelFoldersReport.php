<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders;

use Imanghafoori\LaravelMicroscope\ErrorReporters\Reporting;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class LaravelFoldersReport
{
    use Reporting;

    /**
     * @param  array<string, array<string, \Generator<int, PhpFileDescriptor>>>  $foldersStats
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
     * @param  iterable<string, \Generator<int, PhpFileDescriptor>>  $stats
     * @return array
     */
    private static function subDirs($stats)
    {
        $c = $total = 0;
        $sub = '';
        foreach ($stats as $dir => $files) {
            $c++;
            $filesCount = 0;
            // consume generator:
            foreach ($files as $_file) {
                $filesCount++;
            }

            $total += $filesCount;
            $filesCount && ($sub .= self::addLine($dir, $filesCount));
        }

        return [$total, $sub, $c];
    }
}
