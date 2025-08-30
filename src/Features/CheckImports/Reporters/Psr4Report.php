<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\AutoloadMessages;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Psr4ReportPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Reporting;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use JetBrains\PhpStorm\Pure;

class Psr4Report
{
    use Reporting;

    public static $callback;

    /**
     * @param  array<string, array<string, array<string, (callable(): int)>>>  $psr4Stats
     * @param  array<string, array<string, \Generator<int, PhpFileDescriptor>>>  $classMapStats
     * @param  \Illuminate\Console\OutputStyle  $console
     * @return void
     */
    public static function formatAndPrintAutoload($psr4Stats, $classMapStats, $console)
    {
        $lines = self::getConsoleMessages($psr4Stats, $classMapStats);

        Psr4ReportPrinter::printAll($lines, $console);
    }

    #[Pure]
    public static function formatComposerPath($composerPath)
    {
        $composerPath = trim($composerPath, '/');
        $composerPath = $composerPath ? trim($composerPath, '/').'/' : '';

        return ' <fg=blue>./'.$composerPath.'composer.json'.'</>';
    }

    /**
     * @param  string  $composerPath
     * @param  array<string, array<string, (callable(): int)>>  $psr4Stat
     * @param  array<string, array<string, \Generator<int, PhpFileDescriptor>>>  $classMapStat
     * @param  array<string, \Generator<int, PhpFileDescriptor>>  $filesStat
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
     * @param  array<string, array<string, array<string, (callable(): int)>>>  $psr4Stats
     * @param  array<string, array<string, \Generator<int, PhpFileDescriptor>>>  $classMapStats
     * @param  array<string, \Generator<int, PhpFileDescriptor>>  $filesStat
     * @return array<int, array<int, string|\Generator<int, string>>>
     */
    public static function getConsoleMessages($psr4Stats, $classMapStats, $filesStat = [])
    {
        $cb = fn ($psr4Stat, $key) => self::present(
            $key, $psr4Stat, $classMapStats[$key] ?? null, $filesStat[$key] ?? null
        );

        return Loop::map($psr4Stats, $cb);
    }
}
