<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\AutoloadMessages;

use Imanghafoori\LaravelMicroscope\ErrorReporters\Reporting;
use JetBrains\PhpStorm\Pure;

class ClassMapStats
{
    use Reporting;

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto  $stat
     * @param  \Closure  $callback
     * @return string|void
     */
    #[Pure]
    public static function getLines($stat, $callback)
    {
        $lines = '';
        $c = $total = 0;

        foreach ($stat->stats as $path => $files) {
            $count = count(iterator_to_array($files->files));
            if (! $count) {
                continue;
            }
            $total += $count;
            $c++;
            $lines .= self::addLine($path, $count);
            $callback && $callback($path, $count);
        }

        if ($total) {
            $c === 1 && $total = '';

            return self::blue($total).'classmap:'.$lines;
        }
    }
}
