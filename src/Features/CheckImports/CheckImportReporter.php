<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;

class CheckImportReporter
{
    /**
     * @param  array<string, array<string, array<string, int>>>  $psr4Stats
     * @return string
     */
    public static function printPsr4(array $psr4Stats)
    {
        $output = '';
        foreach ($psr4Stats as $composerPath => $psr4) {
            $output .= self::formatComposerPath($composerPath).PHP_EOL;
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
     * @param array<string, array<string, int>>  $psr4
     * @return string
     */
    public static function formatPsr4Stats(array $psr4)
    {
        $maxLen = self::getMaxLength($psr4);
        $result = '';
        foreach ($psr4 as $psr4Namespace => $psr4Paths) {
            foreach ($psr4Paths as $path => $countClasses) {
                $result .= self::hyphen().'<fg=red>'.self::paddedNamespace($maxLen, $psr4Namespace).' </>';
                $result .= PHP_EOL.'    '.self::blue($countClasses).'file'.($countClasses == 1 ? '' : 's').' found ('.self::green('./'.$path).")".PHP_EOL;
            }
        }

        return $result;
    }

    /**
     * @param array<string, array<string, int>> $psr4
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

    /**
     * @param array<string, array<string, array<string, array<int, string>>>> $foldersStats
     * @return string
     */
    public static function foldersStats($foldersStats)
    {
        $output = '';
        foreach ($foldersStats as $fileType => $stats) {
            $total = 0;
            foreach ($stats as $dir => $files) {
                $total += count($files);
            }

            $output .= self::blue($total).$fileType;

            foreach ($stats as $dir => $files) {
                $count = count($files);
                $count && ($output .= self::addLine($dir, $count));
            }

            $output .= PHP_EOL;
        }

        return trim($output, PHP_EOL);
    }

    public static function totalImportsMsg($refCount)
    {
        return '<options=bold;fg=yellow>'.$refCount.' imports were checked under:</>';
    }

    /**
     * @param array<string, int> $stats
     * @param int $filesCount
     * @return string
     */
    public static function getBladeStats($stats, $filesCount): string
    {
        $output = self::blue($filesCount).'blade'.($filesCount <= 1 ? '' : 's');
        foreach ($stats as $path => $count) {
            $count && ($output .= self::addLine($path, $count));
        }

        return $output;
    }

    public static function getRouteStats($count)
    {
        return self::blue($count).' route'.($count <= 1 ? '' : 's');
    }

    public static function getFilesStats($count)
    {
        return self::blue($count).'class'.($count <= 1 ? '' : 'es');
    }

    public static function normalize($dirPath)
    {
        return FilePath::normalize(str_replace(base_path(), '.', $dirPath));
    }

    public static function green(string $string)
    {
        return '<fg=green>'.$string.'</>';
    }

    public static function hyphen()
    {
        return '   ➖  ';
    }

    public static function files($count)
    {
        return ' ( '.$count.' files )';
    }

    public static function addLine($path, $count)
    {
        $output = PHP_EOL.'    '.self::hyphen();
        $output .= self::green(self::normalize($path));
        $output .= self::files($count);

        return $output;
    }

    public static function blue($filesCount)
    {
        return self::hyphen().'<fg=blue>'.$filesCount.'</> ';
    }

    public static function paddedNamespace($longest, $namespace)
    {
        $padLength = $longest - strlen($namespace);

        return $namespace.str_repeat(' ', $padLength);
    }

    public static function paddedClassCount($countClasses)
    {
        return str_pad((string) $countClasses, 3, ' ', STR_PAD_LEFT);
    }

    public static function header(): string
    {
        return ' ⬛️ <fg=blue>Overall:</>';
    }

    public static function formatErrorSummary($totalCount, $checkedRefCount)
    {
        return '<options=bold;fg=yellow>'.$checkedRefCount.' references were checked, '.$totalCount.' error'.($totalCount == 1 ? '' : 's').' found.</>';
    }

    public static function format($errorType, $count)
    {
        return ' 🔸 <fg=yellow>'.$count.'</> '.$errorType.($count == 1 ? '' : 's').' found.';
    }
}
