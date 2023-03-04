<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\Checks\CheckClassReferences;
use Imanghafoori\LaravelMicroscope\Checks\FacadeAliases;
use Imanghafoori\LaravelMicroscope\ErrorReporters\CheckImportReporter;
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

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->line('');
        $this->info('Checking imports...');

        $this->option('nofix') && config(['microscope.no_fix' => true]);

        $errorPrinter->printer = $this->output;

        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');

        $routeFiles = FilePath::removeExtraPaths(
            RoutePaths::get(),
            $fileName,
            $folder
        );

        $this->checkFilePaths($routeFiles);

        $paths = ComposerJson::readAutoloadFiles();

        $basePath = base_path();
        foreach (ComposerJson::make()->readAutoloadClassMap() as $compPath => $classmaps) {
            foreach ($classmaps as $classmap) {
                $compPath = trim($compPath, '/') ? trim($compPath, '/').DIRECTORY_SEPARATOR : '';
                $classmap = $basePath.DIRECTORY_SEPARATOR.$compPath.$classmap;
                $paths = array_merge($paths, array_values(ClassMapGenerator::createMap($classmap)));
            }
        }

        $this->checkFilePaths(FilePath::removeExtraPaths(
            $paths,
            $fileName,
            $folder
        ));

        $foldersStats = $this->checkFolders([
            'config' => app()->configPath(),
            'seeds' => LaravelPaths::seedersDir(),
            'migrations' => LaravelPaths::migrationDirs(),
            'factories' => LaravelPaths::factoryDirs(),
        ], $fileName, $folder);

        $paramProvider = function ($tokens) {
            $imports = ParseUseStatement::parseUseStatements($tokens);

            return $imports[0] ?: [$imports[1]];
        };
        FacadeAliases::$command = $this;
        $psr4Stats = ForPsr4LoadedClasses::check([
            CheckClassReferencesAreValid::class,
            FacadeAliases::class,
        ], $paramProvider, $fileName, $folder);

        // Checks the blade files for class references.
        $bladeStats = BladeFiles::check([CheckClassReferences::class], $fileName, $folder);

        $this->finishCommand($errorPrinter);
        CheckImportReporter::report($this, $psr4Stats, $foldersStats, $bladeStats, count($routeFiles));

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

    private function checkFolders($dirsList, $file, $folder)
    {
        $fileCounts = [];
        foreach ($dirsList as $listName => $dirs) {
            $filePaths = Paths::getAbsFilePaths($dirs, $file, $folder);
            $this->checkFilePaths($filePaths);

            $fileCounts[$listName] = [
                'paths' => $dirs,
                'fileCount' => count($filePaths),
            ];
        }

        return $fileCounts;
    }
}
