<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;

class CheckImportReporter
{
    /**
     * @var CheckImportsCommand
     */
    private static $command;

    public static function report(CheckImportsCommand $command, array $psr4Stats, array $foldersStats, array $bladeStats, int $countRouteFiles): void
    {
        self::$command = $command;

        $command->getOutput()->writeln(self::totalImportsMsg());

        self::printPsr4($psr4Stats);

        self::printFileCounts($foldersStats, $bladeStats, $countRouteFiles);

        self::printErrorsCount();
    }

    private static function printFileCounts($foldersStats, $bladeStats, int $countRouteFiles): string
    {
        $output = ' <fg=blue>Overall'."</>\n";
        $output .= '   - <fg=blue>'.ForPsr4LoadedClasses::$checkedFilesNum.'</> class'.(ForPsr4LoadedClasses::$checkedFilesNum <= 1 ? '' : 'es').PHP_EOL;

        $output .= '   - <fg=blue>'.BladeFiles::$checkedFilesNum.'</> blade'.(BladeFiles::$checkedFilesNum <= 1 ? '' : 's').' (';
        $numBladeStats = count($bladeStats);
        $i = 0;
        foreach ($bladeStats as $path => $count) {
            $path = FilePath::normalize(str_replace(base_path(), '.', $path));
            $output .= '<fg=green>'.$path.'</>';
            if (++$i !== $numBladeStats) {
                $output .= ','.PHP_EOL.'        ';
            }
        }
        $output .= ')'.PHP_EOL;

        $output = self::foldersStats($foldersStats, $output);

        $output .= '   - <fg=blue>'.$countRouteFiles.'</> route'.($countRouteFiles <= 1 ? '' : 's').PHP_EOL;
        self::$command->line($output);

        return $output;
    }

    private static function printErrorsCount(): void
    {
        $totalErrors = ImportsAnalyzer::$unusedImportsCount + ImportsAnalyzer::$wrongImportsCount;
        $output = '<options=bold;fg=yellow>'.ImportsAnalyzer::$refCount.' refs were checked, '.$totalErrors.' error'.($totalErrors == 1 ? '' : 's').' found.</>'.PHP_EOL;
        $output .= ' - <fg=yellow>'.ImportsAnalyzer::$unusedImportsCount.' unused</> import'.(ImportsAnalyzer::$unusedImportsCount == 1 ? '' : 's').' found.'.PHP_EOL;
        $output .= ' - <fg=red>'.ImportsAnalyzer::$wrongImportsCount.' wrong</> import'.(ImportsAnalyzer::$wrongImportsCount <= 1 ? '' : 's').' found.'.PHP_EOL;
        $output .= ' - <fg=red>'.ImportsAnalyzer::$wrongClassRefCount.' wrong</> class'.(ImportsAnalyzer::$wrongClassRefCount <= 1 ? '' : 'es').' ref found.';
        self::$command->getOutput()->writeln($output);
    }

    private static function printPsr4(array $psr4Stats): void
    {
        /**
         * @var CheckImportsCommand $command
         */
        $command = self::$command;
        $spaces = self::getMaxLength($psr4Stats);

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
            $command->getOutput()->writeln($output);
        }
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

    private static function foldersStats($foldersStats, string $output)
    {
        foreach ($foldersStats as $fileType => $stats) {
            $output .= '   - <fg=blue>'.$stats['fileCount'].'</> '.$fileType;
            if (empty($stats['paths'])) {
                $output .= PHP_EOL;
                continue;
            }
            $output .= '  (';
            $paths = (array) $stats['paths'];
            $numPaths = count($paths);
            $i = 0;
            foreach ($paths as $path) {
                $path = FilePath::normalize(str_replace(base_path(), '.', $path));
                $output .= '<fg=green>'.$path.'</>';
                if (++$i !== $numPaths) {
                    $output .= ','.PHP_EOL.'        ';
                }
            }
            $output .= ')'.PHP_EOL;
        }

        return $output;
    }

    private static function totalImportsMsg(): string
    {
        return '<options=bold;fg=yellow>'.ImportsAnalyzer::$refCount.' imports were checked under:</>';
    }
}
