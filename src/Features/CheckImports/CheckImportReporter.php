<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Iterators\ChecksOnPsr4Classes;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class CheckImportReporter
{
    public static function report($psr4Stats, $foldersStats, $bladeStats, int $routeFilesCount)
    {
        return [
            self::totalImportsMsg(ImportsAnalyzer::$checkedRefCount),
            self::printPsr4($psr4Stats),
            self::printFileCounts($foldersStats, $bladeStats, $routeFilesCount),
        ];
    }

    private static function printFileCounts($foldersStats, $bladeStats, int $countRouteFiles): string
    {
        $output = ' <fg=blue>Overall:'."</>\n";
        $output .= self::compileCheckedFilesStats(ChecksOnPsr4Classes::$checkedFilesCount);
        $output .= self::compileBladeStats($bladeStats);
        $output .= self::compileFolderStats($foldersStats);
        $output .= self::getRouteStats($countRouteFiles);

        return $output;
    }

    private static function compileCheckedFilesStats($checkedFilesCount): string
    {
        return $checkedFilesCount ? self::getFilesStats($checkedFilesCount) : '';
    }

    private static function compileBladeStats($bladeStats): string
    {
        return $bladeStats ? self::getBladeStats($bladeStats, BladeFiles::$checkedFilesCount) : '';
    }

    private static function compileFolderStats($foldersStats): string
    {
        return $foldersStats ? self::foldersStats($foldersStats) : '';
    }

    public static function printErrorsCount($errorsList)
    {
        $counts = self::calculateErrorCounts(
            count($errorsList['wrongClassRef'] ?? []),
            count($errorsList['extraCorrectImport'] ?? []),
            count($errorsList['extraWrongImport'] ?? [])
        );

        $output = self::formatErrorSummary($counts['totalErrors'], ImportsAnalyzer::$checkedRefCount);
        $output .= self::formatDetail('unused import', $counts['extraImportsCount']);
        $output .= self::formatDetail('wrong import', $counts['wrongCount']);
        $output .= self::formatDetail('wrong class reference', $counts['wrongUsedClassCount']);

        return $output;
    }

    private static function calculateErrorCounts($wrongUsedClassCount, $extraCorrectImportsCount, $extraWrongImportCount): array
    {
        return [
            'wrongCount' => $extraWrongImportCount,
            'wrongUsedClassCount' => $wrongUsedClassCount,
            'extraImportsCount' => $extraCorrectImportsCount + $extraWrongImportCount,
            'totalErrors' => $wrongUsedClassCount + $extraCorrectImportsCount + $extraWrongImportCount,
        ];
    }

    private static function formatErrorSummary($totalCount, $checkedRefCount): string
    {
        return '<options=bold;fg=yellow>'.$checkedRefCount.' references were checked, '.$totalCount.' error'.($totalCount == 1 ? '' : 's').' found.</>'.PHP_EOL;
    }

    private static function formatDetail($errorType, $count): string
    {
        return ' - <fg=yellow>'.$count.' '.$errorType.($count == 1 ? '' : 's').' found.'.PHP_EOL;
    }

    /**
     * @param array<string, array<string, string[]>> $psr4Stats
     *
     * @return string
     */
    public static function printPsr4(array $psr4Stats)
    {
        $output = '';
        foreach ($psr4Stats as $composerPath => $psr4) {
            $output .= self::formatComposerPath($composerPath);
            $output .= self::formatPsr4Stats($psr4);
        }

        return $output;
    }

    private static function formatComposerPath($composerPath): string
    {
        $composerPath = trim($composerPath, '/');
        $composerPath = $composerPath ? trim($composerPath, '/').'/' : '';

        return ' <fg=blue>./'.$composerPath.'composer.json'.'</>'.PHP_EOL;
    }

    /**
     * @param array<string, string[]> $psr4
     *
     * @return string
     */
    private static function formatPsr4Stats(array $psr4)
    {
        $maxLen = self::getMaxLength($psr4);
        $result = '';
        foreach ($psr4 as $psr4Namespace => $psr4Paths) {
            foreach ($psr4Paths as $path => $countClasses) {
                $result .= self::hyphen().'<fg=red>'.self::paddedNamespace($maxLen, $psr4Namespace).' </>';
                $result .= self::blue(' '.self::paddedClassCount($countClasses))."file".($countClasses == 1 ? '' : 's').' found ('.self::green('./'.$path).")\n";
            }
        }

        return $result;
    }

    /**
     * @param array<string, string[]> $psr4
     * @return int
     */
    private static function getMaxLength(array $psr4)
    {
        $lengths = [1];
        foreach ($psr4 as $psr4Namespace => $psr4Paths) {
            $lengths[] = strlen($psr4Namespace);
        }

        return max($lengths);
    }

    private static function foldersStats($foldersStats)
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

        return $output;
    }

    public static function totalImportsMsg($checkedRefCount)
    {
        return '<options=bold;fg=yellow>'.$checkedRefCount.' imports were checked under:</>';
    }

    private static function getBladeStats($stats, $filesCount): string
    {
        $output = self::blue($filesCount).'blade'.($filesCount <= 1 ? '' : 's');
        foreach ($stats as $path => $count) {
            $count && ($output .= self::addLine($path, $count));
        }

        $output .= PHP_EOL;

        return $output;
    }

    private static function getRouteStats($count)
    {
        return self::blue($count).' route'.($count <= 1 ? '' : 's').PHP_EOL;
    }

    private static function getFilesStats($count)
    {
        return self::blue($count).' class'.($count <= 1 ? '' : 'es').PHP_EOL;
    }

    private static function normalize($dirPath)
    {
        return FilePath::normalize(str_replace(base_path(), '.', $dirPath));
    }

    private static function green(string $string)
    {
        return '<fg=green>'.$string.'</>';
    }

    private static function hyphen2()
    {
        return PHP_EOL.'     '.self::hyphen();
    }

    private static function hyphen()
    {
        return '   - ';
    }

    private static function files($count)
    {
        return ' ( '.$count.' files )';
    }

    private static function addLine($path, $count)
    {
        $output = self::hyphen();
        $output .= self::green(self::normalize($path));
        $output .= self::files($count);

        return $output;
    }

    private static function blue($checkedFilesNum)
    {
        return self::hyphen().'<fg=blue>'.$checkedFilesNum.'</> ';
    }

    private static function paddedNamespace($longest, $namespace)
    {
        $padLength = $longest - strlen($namespace);

        return $namespace.str_repeat(' ', $padLength);
    }

    private static function paddedClassCount($countClasses)
    {
        return str_pad((string) $countClasses, 3, ' ', STR_PAD_LEFT);
    }
}
