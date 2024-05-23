<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

use Generator;
use JetBrains\PhpStorm\Pure;

class Psr4Report
{
    use Reporting;

    public static $callback;

    /**
     * @param  array<string, \Generator>  $psr4Stats
     * @return string
     */
    #[Pure]
    public static function printAutoload($psr4Stats, $classMapStats)
    {
        $callback = function ($composerPath, $psr4, $classMapStats) {
            return self::present($composerPath, $psr4, $classMapStats);
        };

        $outputAll = '';
        foreach ($psr4Stats as $composerPath => $psr4) {
            $output = $callback($composerPath, $psr4, $classMapStats);
            $outputAll .= $output;
        }

        return trim($outputAll);
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
        $i = 0;
        foreach ($psr4 as $psr4Namespace => $psr4Paths) {
            $folders = self::getFolders($psr4Paths);
            if (! $folders) {
                continue;
            }
            self::$callback && (self::$callback)();
            $i++;
            $lengths[] = strlen($psr4Namespace);
            $lines[$i][0] = PHP_EOL.self::getPsr4Head();
            $lines[$i][1] = $psr4Namespace;
            $lines[$i][2] = $folders;
        }

        return self::concatinate(max($lengths), $lines);
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
                $result[$i - 1][0] = PHP_EOL.str_repeat(' ', 12).'- ';
                $result[$i][0] = PHP_EOL.str_repeat(' ', 12).'- ';
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

    #[Pure]
    private static function present(string $composerPath, Generator $psr4, $classMapStats)
    {
        $output = '';
        $output .= PHP_EOL;
        $output .= self::formatComposerPath($composerPath);
        $output .= PHP_EOL;
        $output .= self::hyphen('<options=bold;fg=white>PSR-4 </>');
        $output .= self::formatPsr4Stats($psr4);
        if (isset($classMapStats[$composerPath])) {
            $lines = ClassMapStats::getMessage($classMapStats[$composerPath], self::$callback);
            $lines && ($output .= PHP_EOL.$lines);
        }

        return $output;
    }
}
