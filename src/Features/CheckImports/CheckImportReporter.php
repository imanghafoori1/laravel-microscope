<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Iterators\ChecksOnPsr4Classes;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class CheckImportReporter
{
    public static function report(array $psr4Stats, array $foldersStats, array $bladeStats, int $countRouteFiles)
    {
        return [
            self::totalImportsMsg(),
            self::printPsr4($psr4Stats),
            self::printFileCounts($foldersStats, $bladeStats, $countRouteFiles),
            self::printErrorsCount(),
        ];
    }

    private static function printFileCounts($foldersStats, $bladeStats, int $countRouteFiles): string
    {
        $output = ' <fg=blue>Overall:'."</>\n";
        $output .= self::getFilesStats(ChecksOnPsr4Classes::$checkedFilesCount);

        if ($bladeStats) {
            $output .= self::getBladeStats($bladeStats, BladeFiles::$checkedFilesCount);
        }

        if ($foldersStats) {
            $output .= self::foldersStats($foldersStats);
        }

        if ($countRouteFiles) {
            $output .= self::getRouteStats($countRouteFiles);
        }

        return $output;
    }

    public static function printErrorsCount()
    {
        $printer = ErrorPrinter::singleton();
        $wrongUsedClassCount = count($printer->errorsList['wrongClassRef'] ?? []);
        $extraCorrectImportsCount = count($printer->errorsList['extraCorrectImport'] ?? []);
        $extraWrongImportCount = count($printer->errorsList['extraWrongImport'] ?? []);

        $wrongCount = $extraWrongImportCount;
        $extraImportsCount = $extraCorrectImportsCount + $extraWrongImportCount;
        $totalErrors = $wrongUsedClassCount + $extraCorrectImportsCount + $extraWrongImportCount;

        $output = '<options=bold;fg=yellow>'.ImportsAnalyzer::$checkedRefCount.' references were checked, '.$totalErrors.' error'.($totalErrors == 1 ? '' : 's').' found.</>'.PHP_EOL;
        $output .= ' - <fg=yellow>'.$extraImportsCount.' unused</> import'.($extraImportsCount == 1 ? '' : 's').' found.'.PHP_EOL;
        $output .= ' - <fg=red>'.$wrongCount.' wrong</> import'.($wrongCount <= 1 ? '' : 's').' found.'.PHP_EOL;
        $output .= ' - <fg=red>'.$wrongUsedClassCount.' wrong</> class reference'.($wrongUsedClassCount <= 1 ? '' : 's').' found.';

        return $output;
    }

    public static function printPsr4(array $psr4Stats)
    {
        $spaces = self::getMaxLength($psr4Stats);
        $result = '';
        foreach ($psr4Stats as $composerPath => $psr4) {
            $composerPath = trim($composerPath, '/');
            $composerPath = $composerPath ? trim($composerPath, '/').'/' : '';
            $output = ' <fg=blue>./'.$composerPath.'composer.json'.'</>'.PHP_EOL;
            foreach ($psr4 as $psr4Namespace => $psr4Paths) {
                foreach ($psr4Paths as $path => $countClasses) {
                    $countClasses = str_pad((string) $countClasses, 3, ' ', STR_PAD_LEFT);
                    $len = strlen($psr4Namespace);
                    $output .= '   - <fg=red>'.$psr4Namespace.str_repeat(' ', $spaces - $len).' </>';
                    $output .= " <fg=blue>$countClasses </>file".($countClasses == 1 ? '' : 's').' found (<fg=green>./'.$path."</>)\n";
                }
            }
            $result .= $output.PHP_EOL;
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
            $numPaths = count($stats);
            $output .= self::hyphen();
            $i = 0;
            foreach ($stats as $dir => $files) {
                $count = count($files);
                $output .= self::addLine($dir, $count, ++$i, $numPaths);
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
        $numPaths = count($stats);
        $output .= self::hyphen();
        $i = 0;
        foreach ($stats as $path => $count) {
            $output .= self::addLine($path, $count, ++$i, $numPaths);
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

    private static function addLine($path, $count, $i, $numPaths)
    {
        $output = self::green(self::normalize($path));
        $output .= self::files($count);
        if ($i !== $numPaths) {
            $output .= self::hyphen();
        }

        return $output;
    }

    private static function blue($checkedFilesNum)
    {
        return '   - <fg=blue>'.$checkedFilesNum.'</> ';
    }
}
