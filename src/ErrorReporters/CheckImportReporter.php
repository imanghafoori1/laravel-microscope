<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\Checks\CheckClassReferences;
use Imanghafoori\LaravelMicroscope\Commands\CheckImports;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;

class CheckImportReporter
{
    public static function report(CheckImports $command, array $psr4Stats, array $foldersStats, array $bladeStats, int $countRouteFiles): void
    {
        $command->getOutput()->writeln('<options=bold;fg=yellow>'.CheckClassReferences::$refCount.' import'.(CheckClassReferences::$refCount == 1 ? '' : 's').' were checked under:</>');

        $len = 0;
        foreach ($psr4Stats as $composerPath => $psr4) {
            $output = ' <fg=blue>./'.trim($composerPath.'/', '/').'composer.json'.'</>'.PHP_EOL;
            foreach ($psr4 as $psr4Namespace => $psr4Paths) {
                foreach ($psr4Paths as $path => $countClasses) {
                    $max = max($len, strlen($psr4Namespace));
                    $len = strlen($psr4Namespace);
                    $output .= '   - <fg=red>'.$psr4Namespace.str_repeat(' ', $max - strlen($psr4Namespace)).' </>';
                    $output .= " <fg=blue>$countClasses </>class".($countClasses == 1 ? '' : 'es').' found (<fg=green>./'.$path."</>)\n";
                }
            }
            $command->getOutput()->writeln($output);
        }

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
            $path = str_replace(base_path(), '.', $path);
            $output .= '<fg=green>'.$path.'</>';
            if (++$i !== $numBladeStats) {
                $output .= ', ';
            }
        }
        $output .= ')'.PHP_EOL;

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
                $isEnd = end($paths) == $path;
                $path = str_replace(base_path(), '.', $path);
                $output .= '<fg=green>'.$path.'</>';
                if (++$i !== $numPaths) {
                    $output .= ', ';
                }
            }
            $output .= ')'.PHP_EOL;
        }

        $output .= '   - <fg=blue>'.$countRouteFiles.'</> route'.($countRouteFiles <= 1 ? '' : 's').PHP_EOL;
        $command->line($output);

        return $output;
    }

    private static function printErrorsCount(CheckImports $command): void
    {
        $totalErrors = CheckClassReferences::$unusedImportsCount + CheckClassReferences::$wrongImportsCount;
        $output = '<options=bold;fg=yellow>'.$totalErrors.' error'.($totalErrors == 1 ? '' : 's').' found.</>'.PHP_EOL;
        $output .= ' - <fg=yellow>'.CheckClassReferences::$unusedImportsCount.' unused</> import'.(CheckClassReferences::$unusedImportsCount == 1 ? '' : 's').' found.'.PHP_EOL;
        $output .= ' - <fg=red>'.CheckClassReferences::$wrongImportsCount.' wrong</> import'.(CheckClassReferences::$wrongImportsCount <= 1 ? '' : 's').' found.'.PHP_EOL;
        $output .= ' - <fg=red>'.CheckClassReferences::$wrongClassRefCount.' wrong</> class'.(CheckClassReferences::$wrongClassRefCount <= 1 ? '' : 'es').' ref found.';
        $command->getOutput()->writeln($output);
    }
}
