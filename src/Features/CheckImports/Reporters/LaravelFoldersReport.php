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
            $total = 0;
            foreach ($stats as $dir => $files) {
                $total += count($files);
            }

            $total && ($output .= self::blue($total).$fileType);

            foreach ($stats as $dir => $files) {
                $count = count($files);
                $count && ($output .= self::addLine($dir, $count));
            }

            $output .= PHP_EOL;
        }

        return trim($output, PHP_EOL);
    }
}
