<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

class Psr4Report
{
    use Reporting;

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
            $output .= self::formatPsr4Stats($psr4);
            if (isset($classMapStats[$composerPath])) {
                $output .= PHP_EOL.CheckImportReporter::getClassMapStats($classMapStats[$composerPath]);
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
        $maxLen = self::getMaxLength($psr4);
        $result = self::hyphen('<fg=red>PSR-4 </>');
        foreach ($psr4 as $psr4Namespace => $psr4Paths) {
            if (array_sum($psr4Paths) === 0) {
                continue;
            }
            $result .= self::getPsr4($maxLen, $psr4Namespace);
            $result .= self::getFolders($psr4Paths);
        }

        return $result;
    }

    public static function paddedNamespace($longest, $namespace)
    {
        $padLength = $longest - strlen($namespace);

        return $namespace.str_repeat(' ', $padLength);
    }

    /**
     * @param  array<string, array<string, int>>  $psr4
     * @return int
     */
    public static function getMaxLength(array $psr4)
    {
        $lengths = [1];
        foreach ($psr4 as $psr4Namespace => $_) {
            $lengths[] = strlen($psr4Namespace);
        }

        return max($lengths);
    }

    private static function getPsr4(int $maxLen, string $namespace)
    {
        return PHP_EOL.'    '.self::hyphen().'<fg=red>'.self::paddedNamespace($maxLen + 2, $namespace.':').' </>';
    }

    private static function getFolders(array $psr4Paths): string
    {
        $result = '';
        foreach ($psr4Paths as $path => $countClasses) {
            // skip if no file was found
            if (! $countClasses) {
                continue;
            }
            $result .= count($psr4Paths) > 1 ? PHP_EOL.str_repeat(' ', 12).'- ' : str_repeat(' ', 6);
            $result .= self::green('./'.$path);
            $result .= '      ( '.$countClasses.' file'.($countClasses == 1 ? '' : 's').' )';
        }

        return $result;
    }
}
