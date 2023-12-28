<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

class Psr4Report
{
    use Reporting;

    public static $callback;

    /**
     * @param  array<string, array<string, array<string, int>>>  $psr4Stats
     * @return string
     */
    public static function printAutoload($psr4Stats, $classMapStats)
    {
        $output = '';
        foreach ($psr4Stats as $composerPath => $psr4) {
            $output .= PHP_EOL;
            $output .= self::formatComposerPath($composerPath);
            $output .= PHP_EOL;
            $output .= self::hyphen('<options=bold;fg=white>PSR-4 </>');
            $output .= self::formatPsr4Stats($psr4);
            if (isset($classMapStats[$composerPath])) {
                $output .= PHP_EOL.CheckImportReporter::getClassMapStats(
                    $classMapStats[$composerPath], self::$callback
                );
            }
        }

        return trim($output);
    }

    public static function formatComposerPath($composerPath): string
    {
        $composerPath = trim($composerPath, '/');
        $composerPath = $composerPath ? trim($composerPath, '/').'/' : '';

        return ' <fg=blue>./'.$composerPath.'composer.json'.'</>';
    }

    /**
     * @param  array<string, array<string, int>>  $psr4
     * @return string
     */
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
            (self::$callback)();
            $i++;
            $lengths[] = strlen($psr4Namespace);
            $lines[$i][0] = self::getPsr4Head();
            $lines[$i][1] = $psr4Namespace;
            $lines[$i][2] = $folders;
        }

        $longest = max($lengths);

        foreach ($lines as $i => $line) {
            $line[1] = self::getPsr4($longest, $line[1]);
            $lines[$i] = $line[0].$line[1].$line[2];
        }

        return implode('', $lines);
    }

    private static function paddedNamespace($longest, $namespace)
    {
        $padLength = $longest - strlen($namespace);

        return $namespace.str_repeat(' ', $padLength);
    }

    private static function getPsr4Head()
    {
        return PHP_EOL.'    '.self::hyphen().'<fg=red>';
    }

    private static function getPsr4(int $maxLen, string $namespace)
    {
        return self::paddedNamespace($maxLen + 1, $namespace.':').' </>';
    }

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
            $result[$i][2] = ' ( '.$countClasses.' file'.($countClasses == 1 ? '' : 's').' )';
            if ($i > 1) {
                $result[$i - 1][0] = PHP_EOL.str_repeat(' ', 12).'- ';
                $result[$i][0] = PHP_EOL.str_repeat(' ', 12).'- ';
            }
        }

        return self::implode($result);
    }

    private static function implode(array $result)
    {
        $output = '';
        foreach ($result as $res) {
            $output .= implode('', $res);
        }

        return $output;
    }
}
