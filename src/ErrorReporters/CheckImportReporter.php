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
    public static function report(CheckImports $command, $includeFile, $includeFolder, int $countRouteFiles, $psr4Stats, $foldersStats): void
    {
        $command->getOutput()->writeln('<options=bold;fg=yellow>'.CheckClassReferences::$refCount.' import'.(CheckClassReferences::$refCount == 1 ? '' : 's').' were checked under:</>');

        $len = 0;
        foreach (ComposerJson::readAutoload() as $composerPath => $psr4) {
            $output = '';
            $command->getOutput()->writeln(' <fg=blue>./'.trim($composerPath.'/', '/').'composer.json'.'</>');
            foreach ($psr4 as $psr4Namespace => $psr4Paths) {
                $countClasses = 0;
                $skipped = false;
                foreach ((array) $psr4Paths as $psr4Path) {
                    foreach (FilePath::getAllPhpFiles($psr4Path) as $phpFilePath) {
                        $absFilePath = $phpFilePath->getRealPath();
                        if (! FilePath::contains($absFilePath, $includeFile, $includeFolder)) {
                            $skipped = true;
                            continue;
                        }
                        $countClasses++;
                    }
                }
                if ($skipped) {
                    continue;
                }
                $max = max($len, strlen($psr4Namespace));
                $len = strlen($psr4Namespace);
                $output .= '   - <fg=red>'.$psr4Namespace.str_repeat(' ', $max - strlen($psr4Namespace)).' </>';
                $output .= " <fg=blue>$countClasses </>class".($countClasses == 1 ? '' : 'es').' found (<fg=green>./'.$psr4Paths."</>)\n";
            }
            $command->getOutput()->writeln($output);
        }

        self::printFileCounts($includeFile, $includeFolder, $countRouteFiles, $command);

        self::printErrorsCount($command);
    }

    private static function printFileCounts($includeFile, $includeFolder, int $countRouteFiles, CheckImports $command): string
    {
        $output = ' <fg=blue>Overall'."</>\n";
        $countMigrationFiles = count(Paths::getAbsFilePaths(LaravelPaths::migrationDirs(), $includeFile, $includeFolder));
        $countConfigFiles = count(Paths::getAbsFilePaths(app()->configPath(), $includeFile, $includeFolder));
        $output .= '   - <fg=blue>'.ForPsr4LoadedClasses::$checkedFilesNum.'</> class'.(ForPsr4LoadedClasses::$checkedFilesNum <= 1 ? '' : 'es').".\n";
        $output .= '   - <fg=blue>'.BladeFiles::$checkedFilesNum.'</> blade file'.(BladeFiles::$checkedFilesNum <= 1 ? '' : 's').".\n";
        $output .= '   - <fg=blue>'.$countMigrationFiles.'</> migration file'.($countConfigFiles <= 1 ? '' : 's').".\n";
        $output .= '   - <fg=blue>'.$countConfigFiles.'</> config file'.($countConfigFiles <= 1 ? '' : 's').".\n";
        $output .= '   - <fg=blue>'.$countRouteFiles.'</> route file'.($countRouteFiles <= 1 ? '' : 's').".\n";
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
