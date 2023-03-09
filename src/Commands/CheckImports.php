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

    /**
     * @throws \Exception
     */
    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');

        $this->info('Checking imports...');
        $this->line('');

        // Set config for nofix option
        if ($this->option('nofix')) {
            config(['microscope.no_fix' => true]);
        }

        // Set output for error printer
        $errorPrinter->setOutput($this->output);

        // Get file and folder options
        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');

        // Check route files
        $routeFiles = RoutePaths::get();
        $routeFiles = FilePath::removeExtraPaths($routeFiles, $fileName, $folder);
        $this->checkFilePaths($routeFiles);

        // Check autoload files
        $paths = ComposerJson::readAutoloadFiles();
        $classMaps = ComposerJson::make()->readAutoloadClassMap();
        $basePath = base_path();
        foreach ($classMaps as $compPath => $classMapsInPath) {
            foreach ($classMapsInPath as $classMap) {
                $compPath = trim($compPath, '/') ? trim($compPath, '/') . DIRECTORY_SEPARATOR : '';
                $classMap = $basePath . DIRECTORY_SEPARATOR . $compPath . $classMap;
                $paths = array_merge($paths, array_values(ClassMapGenerator::createMap($classMap)));
            }
        }
        $paths = FilePath::removeExtraPaths($paths, $fileName, $folder);
        $this->checkFilePaths($paths);

        // Check folders for config, seeders, migrations, and factories
        $folders = [
            'config' => app()->configPath(),
            'seeds' => LaravelPaths::seedersDir(),
            'migrations' => LaravelPaths::migrationDirs(),
            'factories' => LaravelPaths::factoryDirs(),
        ];
        $foldersStats = $this->checkFolders($folders, $fileName, $folder);

        // Check PSR-4 loaded classes for references
        $paramProvider = function ($tokens) {
            $imports = ParseUseStatement::parseUseStatements($tokens);
            return $imports[0] ?: [$imports[1]];
        };
        FacadeAliases::$command = $this;
        $psr4Stats = ForPsr4LoadedClasses::check([
            CheckClassReferencesAreValid::class,
            FacadeAliases::class,
        ], $paramProvider, $fileName, $folder);

        // Check blade files for class references
        $bladeStats = BladeFiles::check([CheckClassReferences::class], $fileName, $folder);

        // Finish command and report results
        $this->finishCommand($errorPrinter);
        CheckImportReporter::report($this, $psr4Stats, $foldersStats, $bladeStats, count($routeFiles));

        // Print time and thank message if applicable
        $errorPrinter->printTime();
        if (random_int(1, 7) == 2 && Str::startsWith(request()->server('argv')[1] ?? '', 'check:im')) {
            ErrorPrinter::thanks($this);
        }

        $this->line('');

        // Return exit code
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
