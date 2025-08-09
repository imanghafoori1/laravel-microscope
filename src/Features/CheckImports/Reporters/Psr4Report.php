<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

use Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\AutoloadMessages;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Psr4ReportPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Reporting;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use JetBrains\PhpStorm\Pure;

class Psr4Report
{
    use Reporting;

    public static $callback;

    /**
     * @param  array|\Generator  $psr4Stats
     * @param  array<string, \Generator<string, \Generator<int, PhpFileDescriptor>>>  $classMapStats
     * @param  \Illuminate\Console\OutputStyle  $console
     *
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
     * @param  \Generator  $psr4
     * @param  array<string, \Generator<string, \Generator<int, PhpFileDescriptor>>>  $classMapStats
     * @return array
     */
    #[Pure]
    private static function present(string $composerPath, $psr4, $classMapStats, $autoloadedFiles)
    {
        $lines = [];
        $lines[] = PHP_EOL.self::formatComposerPath($composerPath);
        $lines[] = PHP_EOL.self::hyphen('<options=bold;fg=white>PSR-4 </>');
        $lines[] = AutoloadMessages\Psr4Stats::getLines($psr4);

        if (isset($classMapStats[$composerPath])) {
            $line = AutoloadMessages\ClassMapStats::getLines($classMapStats[$composerPath], self::$callback);
            $line && ($lines[] = PHP_EOL.$line);
        }
        if (isset($autoloadedFiles[$composerPath])) {
            $line = AutoloadMessages\AutoloadFiles::getLines($autoloadedFiles[$composerPath]);
            $line && ($lines[] = PHP_EOL.$line);
        }

        return $lines;
    }

    /**
     * @param $psr4Stats
     * @param array $classMapStats
     * @param $autoloadedFiles
     * @return array<int, array>
     */
    public static function getConsoleMessages($psr4Stats, array $classMapStats, $autoloadedFiles = [])
    {
        $lines = [];
        foreach ($psr4Stats as $composerPath => $psr4) {
            $lines[] = self::present($composerPath, $psr4, $classMapStats, $autoloadedFiles);
        }

        return $lines;
    }
}
