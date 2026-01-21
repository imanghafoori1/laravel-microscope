<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Reports;

use Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\AutoloadMessages;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Reporting;
use Imanghafoori\LaravelMicroscope\Foundations\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\AutoloadStats;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use JetBrains\PhpStorm\Pure;

class ComposerJsonReport
{
    use Reporting;

    public static $callback;

    #[Pure]
    public static function formatComposerPath($composerPath)
    {
        $composerPath = trim($composerPath, '/');
        $composerPath = $composerPath ? trim($composerPath, '/').'/' : '';

        return Color::blue(' ./'.$composerPath.'composer.json');
    }

    /**
     * @param  string  $composerPath
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\Psr4StatsDTO  $psr4Stat
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\StatsDto  $classMapStat
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\FilesDto  $filesStat
     * @return array<int, string|\Generator<int, string>>
     */
    #[Pure]
    private static function present($composerPath, $psr4Stat, $classMapStat, $filesStat)
    {
        $max = max(array_map('strlen', array_keys(ComposerJson::readPsr4()[$composerPath])));

        $lines = [];
        $lines[] = PHP_EOL.self::formatComposerPath($composerPath);
        $lines[] = PHP_EOL.self::hyphen(Color::boldYellow('PSR-4 '));
        $lines[] = AutoloadMessages\Psr4Stats::getLines($psr4Stat, $max);

        if ($classMapStat) {
            $line = AutoloadMessages\ClassMapStats::getLines($classMapStat, self::$callback);
            $line && ($lines[] = PHP_EOL.$line);
        }

        if ($filesStat) {
            $line = AutoloadMessages\AutoloadFiles::getLines($filesStat);
            $line && ($lines[] = PHP_EOL.$line);
        }

        return $lines;
    }

    /**
     * @param  array<string, \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\Psr4StatsDTO>  $psr4Stats
     * @param  array<string, \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\StatsDto>  $classMapStats
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\StatsDto  $filesStat
     * @return \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\AutoloadStats
     */
    public static function formatAutoloads($psr4Stats, $classMapStats, $filesStat = [])
    {
        return AutoloadStats::make(Loop::map($psr4Stats, fn ($psr4Stat, $key) => self::present(
            $key,
            $psr4Stat,
            $classMapStats[$key] ?? null,
            $filesStat->stats[$key] ?? null
        )));
    }
}
