<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\Checks\CheckClassReferences;
use Imanghafoori\LaravelMicroscope\Checks\FacadeAliases;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class CheckImports extends Command
{
    use LogsErrors;

    protected $signature = 'check:imports {--w|wrong} {--f|file=} {--d|folder=} {--detailed : Show files being checked} {--s|nofix : avoids the automatic fixes}';

    protected $description = 'Checks the validity of use statements';

    public static $stats = [];

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->line('');
        $this->info('Checking imports...');

        $this->option('nofix') && config(['microscope.no_fix' => true]);

        $errorPrinter->printer = $this->output;

        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');

        $this->checkFilePaths($routeFiles = RoutePaths::get($fileName, $folder));

        $this->checkFolders([
            app()->configPath(),
            LaravelPaths::seedersDir(),
            LaravelPaths::migrationDirs(),
            LaravelPaths::factoryDirs(),
        ], $fileName, $folder);

        $paramProvider = function ($tokens) {
            $imports = ParseUseStatement::parseUseStatements($tokens);

            return $imports[0] ?: [$imports[1]];
        };
        FacadeAliases::$command = $this;
        ForPsr4LoadedClasses::check([CheckClassReferencesAreValid::class, FacadeAliases::class], $paramProvider, $fileName, $folder);

        // Checks the blade files for class references.
        BladeFiles::check([CheckClassReferences::class], $fileName, $folder);

        $this->finishCommand($errorPrinter);
        $this->writeOverall($fileName, $folder, count($routeFiles));

        $errorPrinter->printTime();

        if (random_int(1, 7) == 2 && Str::startsWith(request()->server('argv')[1] ?? '', 'check:im')) {
            ErrorPrinter::thanks($this);
        }
        $this->line('');

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private function checkFilePaths($paths)
    {
        foreach ($paths as $path) {
            $tokens = token_get_all(file_get_contents($path));
            CheckClassReferences::check($tokens, $path);
            CheckClassReferencesAreValid::checkAtSignStrings($tokens, $path, true);
        }
    }

    private function checkFolders($dirs, $file, $folder)
    {
        foreach ($dirs as $dir) {
            $this->checkFilePaths(Paths::getAbsFilePaths($dir, $file, $folder));
        }
    }

    public function writeOverall($includeFile, $includeFolder, int $countRouteFiles)
    {
        $this->getOutput()->writeln('<options=bold;fg=yellow>'.CheckClassReferences::$refCount.' import'.(CheckClassReferences::$refCount == 1 ? '' : 's').' were checked under:</>');

        $len = 0;
        foreach (ComposerJson::readAutoload() as $composerPath => $psr4) {
            $output = '';
            $this->getOutput()->writeln(' <fg=blue>./'.trim($composerPath.'/', '/').'composer.json'.'</>');
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
            $this->getOutput()->writeln($output);
        }

        $output = ' <fg=blue>Overall'."</>\n";
        $countMigrationFiles = count(Paths::getAbsFilePaths(LaravelPaths::migrationDirs(), $includeFile, $includeFolder));
        $countConfigFiles = count(Paths::getAbsFilePaths(app()->configPath(), $includeFile, $includeFolder));
        $output .= '   - <fg=blue>'.ForPsr4LoadedClasses::$checkedFilesNum.'</> class'.(ForPsr4LoadedClasses::$checkedFilesNum <= 1 ? '' : 'es').".\n";
        $output .= '   - <fg=blue>'.BladeFiles::$checkedFilesNum.'</> blade file'.(BladeFiles::$checkedFilesNum <= 1 ? '' : 's').".\n";
        $output .= '   - <fg=blue>'.$countMigrationFiles.'</> migration file'.($countConfigFiles <= 1 ? '' : 's').".\n";
        $output .= '   - <fg=blue>'.$countConfigFiles.'</> config file'.($countConfigFiles <= 1 ? '' : 's').".\n";
        $output .= '   - <fg=blue>'.$countRouteFiles.'</> route file'.($countRouteFiles <= 1 ? '' : 's').".\n";
        $this->line($output);

        $totalErrors = CheckClassReferences::$unusedImportsCount + CheckClassReferences::$wrongImportsCount;
        $output = '<options=bold;fg=yellow>'.$totalErrors.' error'.($totalErrors == 1 ? '' : 's').' found.</>'.PHP_EOL;
        $output .= ' - <fg=yellow>'.CheckClassReferences::$unusedImportsCount.' unused</> import'.(CheckClassReferences::$unusedImportsCount == 1 ? '' : 's').' found.'.PHP_EOL;
        $output .= ' - <fg=red>'.CheckClassReferences::$wrongImportsCount.' wrong</> import'.(CheckClassReferences::$wrongImportsCount <= 1 ? '' : 's').' found.'.PHP_EOL;
        $output .= ' - <fg=red>'.CheckClassReferences::$wrongClassRefCount.' wrong</> class'.(CheckClassReferences::$wrongClassRefCount <= 1 ? '' : 'es').' ref found.';
        $this->getOutput()->writeln($output);
    }
}
