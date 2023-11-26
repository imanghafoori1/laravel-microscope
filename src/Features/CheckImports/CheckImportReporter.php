<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Iterators\ChecksOnPsr4Classes;

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
        $output .= self::getFilesStats(ChecksOnPsr4Classes::$checkedFilesNum);

        if ($bladeStats) {
            $output .= self::getBladeStats($bladeStats, BladeFiles::$checkedFilesNum);
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
        $totalErrors = ImportsAnalyzer::$unusedImportsCount + ImportsAnalyzer::$wrongImportsCount + ImportsAnalyzer::$wrongClassRefCount;
        $output = '<options=bold;fg=yellow>'.ImportsAnalyzer::$refCount.' refs were checked, '.$totalErrors.' error'.($totalErrors == 1 ? ' ' : 's').' found.</>'.PHP_EOL;
        $output .= ' - <fg=yellow>'.ImportsAnalyzer::$unusedImportsCount.' unused</> import'.(ImportsAnalyzer::$unusedImportsCount == 1 ? ' ' : 's').' found.'.PHP_EOL;
        $output .= ' - <fg=red>'.ImportsAnalyzer::$wrongImportsCount.' wrong</> import'.(ImportsAnalyzer::$wrongImportsCount <= 1 ? ' ' : 's').' found.'.PHP_EOL;
        $output .= ' - <fg=red>'.ImportsAnalyzer::$wrongClassRefCount.' wrong</> class'.(ImportsAnalyzer::$wrongClassRefCount <= 1 ? '' : 'es').' ref found.';

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
                    $output .= " <fg=blue>$countClasses </>file".($countClasses == 1 ? ' ' : 's').' found (<fg=green>./'.$path."</>)\n";
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
        return '<options=bold;fg=yellow>'.ImportsAnalyzer::$refCount.' imports were checked under:</>';
    }

    private static function getBladeStats($stats, $checkedFilesNum): string
    {
        $output = self::blue($checkedFilesNum).'blade'.($checkedFilesNum <= 1 ? ' ' : 's');
        $numPaths = count($stats);
        $output .= self::hyphen();
        $i = 0;
        foreach ($stats as $path => $count) {
            $output .= self::addLine($path, $count, ++$i, $numPaths);
        }

        $output .= PHP_EOL;

        return $output;
    }

    private static function getRouteStats($countRouteFiles)
    {
        return '   - <fg=blue>'.$countRouteFiles.'</> route'.($countRouteFiles <= 1 ? ' ' : 's').PHP_EOL;
    }

    private static function getFilesStats($checkedFilesNum)
    {
        return '   - <fg=blue>'.$checkedFilesNum.'</> class'.($checkedFilesNum <= 1 ? '' : 'es').PHP_EOL;
    }

    private static function nextLine(int $numPaths)
    {
        return self::hyphen();
    }

    private static function normalize($dir)
    {
        return FilePath::normalize(str_replace(base_path(), '.', $dir));
    }

    private static function green(string $path)
    {
        return '<fg=green>'.$path.'</>';
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
        $output = '';
        $path = self::normalize($path);
        $output .= self::green($path);
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
