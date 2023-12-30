<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

class ClassMapStats
{
    use Reporting;

    /**
     * @param  \Generator  $stat
     * @param  \Closure  $callback
     * @return string|void
     */
    public static function getMessage($stat, $callback)
    {
        $lines = '';
        $c = $total = 0;

        foreach ($stat as $path => $filePathsGen) {
            $count = count(iterator_to_array($filePathsGen));
            if (! $count) {
                continue;
            }
            $total += $count;
            $c++;
            $lines .= self::addLine($path, $count);
            $callback($path, $count);
        }

        if ($total) {
            $c === 1 && $total = '';

            return self::blue($total).'classmap:'.$lines;
        }
    }
}
