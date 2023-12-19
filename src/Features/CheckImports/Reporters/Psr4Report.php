<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

class Psr4Report
{
    use Reporting;

    /**
     * @param  array<string, array<string, array<string, int>>>  $psr4Stats
     * @return string
     */
    public static function printPsr4(array $psr4Stats)
    {
        $output = '';
        foreach ($psr4Stats as $composerPath => $psr4) {
            $output .= self::formatComposerPath($composerPath);
            $output .= PHP_EOL;
            $output .= self::formatPsr4Stats($psr4);
        }

        return $output;
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
        $result = '';
        foreach ($psr4 as $psr4Namespace => $psr4Paths) {
            foreach ($psr4Paths as $path => $countClasses) {
                if ($countClasses) {
                    $result .= self::hyphen().'<fg=red>'.self::paddedNamespace($maxLen, $psr4Namespace).' </>';
                    $result .= PHP_EOL.'    '.self::blue($countClasses).'file'.($countClasses == 1 ? '' : 's').' found ('.self::green('./'.$path).')'.PHP_EOL;
                }
            }
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
}
