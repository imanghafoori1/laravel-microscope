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
            self::totalImportsMsg(),
            self::printPsr4($psr4Stats),
            self::printFileCounts($foldersStats, $bladeStats, $routeFilesCount),
        ];
    }

    private static function printFileCounts($foldersStats, $bladeStats, int $countRouteFiles): string
    {
        $output = ' <fg=blue>Overall:'."</>\n";
        $output .= self::compileCheckedFilesStats();
        $output .= self::compileBladeStats($bladeStats);
        $output .= self::compileFolderStats($foldersStats);
        $output .= self::getRouteStats($countRouteFiles);

        return $output;
    }

    private static function compileCheckedFilesStats(): string
    {
        $checkedFilesCount = ChecksOnPsr4Classes::$checkedFilesCount;

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
        $counts = self::calculateErrorCounts($errorsList);
        $output = self::formatErrorSummary($counts['totalErrors']);
        $output .= self::formatDetail('unused import', $counts['extraImportsCount']);
        $output .= self::formatDetail('wrong import', $counts['wrongCount']);
        $output .= self::formatDetail('wrong class reference', $counts['wrongUsedClassCount']);

        return $output;
    }

    private static function calculateErrorCounts($errorsList): array
    {
        return [
            'wrongUsedClassCount' => count($errorsList['wrongClassRef'] ?? []),
            'extraCorrectImportsCount' => count($errorsList['extraCorrectImport'] ?? []),
            'extraWrongImportCount' => count($errorsList['extraWrongImport'] ?? []),
            'extraImportsCount' => array_sum([
                count($errorsList['extraCorrectImport'] ?? []),
                count($errorsList['extraWrongImport'] ?? [])
            ]),
            'wrongCount' => count($errorsList['extraWrongImport'] ?? []),
            'totalErrors' => array_sum([
                count($errorsList['wrongClassRef'] ?? []),
                count($errorsList['extraCorrectImport'] ?? []),
                count($errorsList['extraWrongImport'] ?? [])
            ])
        ];
    }

    private static function formatErrorSummary($totalErrors): string
    {
        return '<options=bold;fg=yellow>'.ImportsAnalyzer::$checkedRefCount.' references were checked, '.$totalErrors.' error'.($totalErrors == 1 ? '' : 's').' found.</>'.PHP_EOL;
    }

    private static function formatDetail($errorType, $count): string
    {
        return ' - <fg=yellow>'.$count.' '.$errorType.($count == 1 ? '' : 's').' found.'.PHP_EOL;
    }

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

    private static function formatPsr4Stats($psr4): string
    {
        $spaces = self::getMaxLength($psr4);
        $result = '';
        foreach ($psr4 as $psr4Namespace => $psr4Paths) {
            foreach ($psr4Paths as $path => $countClasses) {
                $countClasses = str_pad((string) $countClasses, 3, ' ', STR_PAD_LEFT);
                $len = strlen($psr4Namespace);
                $result .= '   - <fg=red>'.$psr4Namespace.str_repeat(' ', $spaces - $len).' </>';
                $result .= " <fg=blue>$countClasses </>file".($countClasses == 1 ? '' : 's').' found (<fg=green>./'.$path."</>)\n";
            }
        }

        return $result;
    }

    private static function getMaxLength(array $psr4Stats)
    {
        $lengths = [1];
        foreach ($psr4Stats as $psr4) {
            foreach ($psr4 as $psr4Namespace => $psr4Paths) {
                $lengths[] = strlen($psr4Namespace);
            }
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

    public static function totalImportsMsg()
    {
        return '<options=bold;fg=yellow>'.ImportsAnalyzer::$checkedRefCount.' imports were checked under:</>';
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
        return '   - <fg=blue>'.$count.'</> route'.($count <= 1 ? '' : 's').PHP_EOL;
    }

    private static function getFilesStats($count)
    {
        return '   - <fg=blue>'.$count.'</> class'.($count <= 1 ? '' : 'es').PHP_EOL;
    }

    private static function normalize($dirPath)
    {
        return FilePath::normalize(str_replace(base_path(), '.', $dirPath));
    }

    private static function green(string $string)
    {
        return '<fg=green>'.$string.'</>';
    }

    private static function hyphen()
    {
        return PHP_EOL.'        - ';
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
        return '   - <fg=blue>'.$checkedFilesNum.'</> ';
    }
}
