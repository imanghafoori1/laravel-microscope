<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

use Generator;
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
     */
    public static function formatAndPrintAutoload($psr4Stats, $classMapStats, $console)
    {
        $presentations = self::getPresentations($psr4Stats, $classMapStats);

        Psr4ReportPrinter::printAll($presentations, $console);
    }

    #[Pure]
    public static function formatComposerPath($composerPath)
    {
        $composerPath = trim($composerPath, '/');
        $composerPath = $composerPath ? trim($composerPath, '/').'/' : '';

        return ' <fg=blue>./'.$composerPath.'composer.json'.'</>';
    }

    /**
     * @param  array<string, array<string, int>>  $psr4
     * @return string
     */
    #[Pure]
    public static function formatPsr4Stats($psr4)
    {
        $lengths = [1];
        $lines = [];

        foreach ($psr4 as $psr4Namespace => $psr4Paths) {
            self::$callback && (self::$callback)();
            $lengths[] = strlen($psr4Namespace);
            $lines[0] = PHP_EOL.self::getPsr4Head();
            $lines[1] = self::getPsr4(max($lengths), $psr4Namespace);

            yield implode('', $lines);
            $folders = self::getFolders($psr4Paths);
            if ($folders === '') {
                yield "\x1b[1G\x1b[2K\x1b[1A";
            } else {
                yield $folders;
            }
        }
    }

    #[Pure]
    private static function paddedNamespace($longest, $namespace)
    {
        $padLength = $longest - strlen($namespace);

        return $namespace.str_repeat(' ', $padLength);
    }

    #[Pure]
    private static function getPsr4Head()
    {
        return '    '.self::hyphen().'<fg=red>';
    }

    #[Pure]
    private static function getPsr4(int $maxLen, string $namespace)
    {
        return self::paddedNamespace($maxLen + 1, $namespace.':').' </>';
    }

    /**
     * @param  $psr4Paths
     * @return string
     */
    #[Pure]
    private static function getFolders($psr4Paths): string
    {
        $result = [];
        $i = 0;
        foreach ($psr4Paths as $path => $countClasses) {
            // skip if no file was found
            if (! $countClasses) {
                continue;
            }
            $i++;
            $result[$i] = [];
            $result[$i][0] = str_repeat(' ', 6);
            $result[$i][1] = self::green('./'.$path);
            $result[$i][2] = self::files($countClasses);
            if ($i > 1) {
                $result[$i - 1][0] = str_repeat(' ', 12).'- ';
                $result[$i][0] = str_repeat(' ', 12).'- ';
            }
        }

        return self::implode($result);
    }

    #[Pure]
    private static function implode($lines)
    {
        $output = '';
        foreach ($lines as $segments) {
            $output .= implode('', $segments);
        }

        return $output;
    }

    #[Pure]
    private static function concatinate($longest, array $lines)
    {
        foreach ($lines as $i => $line) {
            $line[1] = self::getPsr4($longest, $line[1]);
            $lines[$i] = implode('', $line);
        }

        return implode('', $lines);
    }

    /**
     * @param  string  $composerPath
     * @param  \Generator  $psr4
     * @param  \Generator<string, \Generator<string, int>>  $classMapStats
     * @param  array<string, \Generator<string, \Generator<int, PhpFileDescriptor>>>  $classMapStats
     * @return array
     */
    #[Pure]
    private static function present(string $composerPath, Generator $psr4, $classMapStats, $autoloadedFilesGen)
    {
        $lines = [];
        $lines[] = PHP_EOL.self::formatComposerPath($composerPath);
        $lines[] = PHP_EOL.self::hyphen('<options=bold;fg=white>PSR-4 </>');
        $lines[] = self::formatPsr4Stats($psr4);

        if (isset($classMapStats[$composerPath])) {
            $line = ClassMapStats::getMessage($classMapStats[$composerPath], self::$callback);
            $line && ($lines[] = PHP_EOL.$line);
        }
        if (isset($autoloadedFilesGen[$composerPath])) {
            $line = AutoloadFiles::getLines($autoloadedFilesGen[$composerPath]);
            $line && ($lines[] = PHP_EOL.$line);
        }
        return $lines;
    }

    public static function getPresentations($psr4Stats, array $classMapStats, $autoloadedFilesGen = [])
    {
        $results = [];
        foreach ($psr4Stats as $composerPath => $psr4) {
            $results[] = self::present($composerPath, $psr4, $classMapStats, $autoloadedFilesGen);
        }

        return $results;
    }
}
