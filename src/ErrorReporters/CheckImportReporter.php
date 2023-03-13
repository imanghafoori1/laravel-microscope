<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\Checks\CheckClassReferences;
use Imanghafoori\LaravelMicroscope\Commands\CheckImports;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;

class CheckImportReporter
{
    public static function report(CheckImports $command, array $psr4Stats, array $foldersStats, array $bladeStats, int $countRouteFiles): void
    {
        $command->getOutput()->writeln('<options=bold;fg=yellow>'.CheckClassReferences::$refCount.' imports were checked under:</>');

        self::printPsr4($psr4Stats, $command);

        self::printFileCounts($command, $foldersStats, $bladeStats, $countRouteFiles);

        self::printErrorsCount($command);
    }

    private static function printFileCounts(CheckImports $command, $foldersStats, $bladeStats, int $countRouteFiles): string
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
        $command->line($output);

        return $output;
    }

    private static function printErrorsCount(CheckImports $command): void
    {
        $totalErrors = CheckClassReferences::$unusedImportsCount + CheckClassReferences::$wrongImportsCount;
        $output = '<options=bold;fg=yellow>'.CheckClassReferences::$refCount.' refs were checked, '.$totalErrors.' error'.($totalErrors == 1 ? '' : 's').' found.</>'.PHP_EOL;
        $output .= ' - <fg=yellow>'.CheckClassReferences::$unusedImportsCount.' unused</> import'.(CheckClassReferences::$unusedImportsCount == 1 ? '' : 's').' found.'.PHP_EOL;
        $output .= ' - <fg=red>'.CheckClassReferences::$wrongImportsCount.' wrong</> import'.(CheckClassReferences::$wrongImportsCount <= 1 ? '' : 's').' found.'.PHP_EOL;
        $output .= ' - <fg=red>'.CheckClassReferences::$wrongClassRefCount.' wrong</> class'.(CheckClassReferences::$wrongClassRefCount <= 1 ? '' : 'es').' ref found.';
        $command->getOutput()->writeln($output);
    }

    private static function printPsr4(array $psr4Stats, CheckImports $command): void
    {
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
}
