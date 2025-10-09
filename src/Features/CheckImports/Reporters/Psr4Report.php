<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\AutoloadMessages;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Reporting;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Iterators\DTO\AutoloadStats;
use JetBrains\PhpStorm\Pure;

class Psr4Report
{
    use Reporting;

    public static $callback;

    #[Pure]
    public static function formatComposerPath($composerPath)
    {
        $composerPath = trim($composerPath, '/');
        $composerPath = $composerPath ? trim($composerPath, '/').'/' : '';

        return ' <fg=blue>./'.$composerPath.'composer.json'.'</>';
    }

    /**
     * @param  string  $composerPath
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\Psr4StatsDTO  $psr4Stat
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto  $classMapStat
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\FilesDto  $filesStat
     * @return array<int, string|\Generator<int, string>>
     */
    #[Pure]
    private static function present($composerPath, $psr4Stat, $classMapStat, $filesStat)
    {
        $max = max(array_map('strlen', array_keys(ComposerJson::readPsr4()[$composerPath])));

        $lines = [];
        $lines[] = PHP_EOL.self::formatComposerPath($composerPath);
        $lines[] = PHP_EOL.self::hyphen('<options=bold;fg=white>PSR-4 </>');
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
     * @param  array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\Psr4StatsDTO>  $psr4Stats
     * @param  array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto>  $classMapStats
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto  $filesStat
     * @return \Imanghafoori\LaravelMicroscope\Iterators\DTO\AutoloadStats
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
