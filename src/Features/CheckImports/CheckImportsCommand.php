<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\Checks\FacadeAliases;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassAtMethod;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\Features\Psr4\HandleErrors;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Handlers\FacadeAliasReplacer;
use Imanghafoori\LaravelMicroscope\Handlers\FacadeAliasReporter;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class CheckImportsCommand extends Command
{
    use LogsErrors;

    protected $signature = 'check:imports
        {--force : fixes without asking}
        {--w|wrong : Only reports wrong imports}
        {--f|file= : Pattern for file names to scan}
        {--d|folder= : Pattern for file names to scan}
        {--detailed : Show files being checked}
        {--s|nofix : avoids the automatic fixes}
    ';

    protected $description = 'Checks the validity of use statements';

    /**
     * @var string[]
     */
    private $checks = [
        1 => CheckClassAtMethod::class,
        2 => CheckClassReferencesAreValid::class,
        3 => FacadeAliases::class,
    ];

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->line('');
        $this->info('Checking imports...');

        FacadeAliases::$command = $this;

        if ($this->option('nofix')) {
            config(['microscope.no_fix' => true]);
            FacadeAliases::$handler = FacadeAliasReporter::class;
        }

        if ($this->option('force')) {
            FacadeAliasReplacer::$forceReplace = true;
        }

        if ($this->option('wrong')) {
            CheckClassReferencesAreValid::$checkUnused = false;
            unset($this->checks[3]);
        }

        $errorPrinter->printer = $this->output;

        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');

        $paramProvider = $this->getParamProvider();

        $paths = array_merge(
            $this->getAutoloadFiles(),
            $routeFiles = RoutePaths::get()
        );

        $paths = FilePath::removeExtraPaths(
            $paths,
            $fileName,
            $folder
        );

        $this->checkFilePaths($paths, $paramProvider);

        $foldersStats = $this->checkFolders([
            'config' => app()->configPath(),
            'seeds' => LaravelPaths::seedersDir(),
            'migrations' => LaravelPaths::migrationDirs(),
            'factories' => LaravelPaths::factoryDirs(),
        ], $fileName, $folder, $paramProvider);

        $psr4Stats = ForPsr4LoadedClasses::check($this->checks, $paramProvider, $fileName, $folder);
        $bladeStats = BladeFiles::check($this->checks, $paramProvider, $fileName, $folder);

        $this->finishCommand($errorPrinter);
        CheckImportReporter::report($this, $psr4Stats, $foldersStats, $bladeStats, count($routeFiles));

        $errorPrinter->printTime();

        if ($this->shouldRequestThanks()) {
            ErrorPrinter::thanks($this);
        }
        $this->line('');

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private function checkFilePaths($paths, $paramProvider)
    {
        foreach ($paths as $absFilePath) {
            $tokens = token_get_all(file_get_contents($absFilePath));
            foreach ($this->checks as $check) {
                $check::check($tokens, $absFilePath, $paramProvider($tokens));
            }
        }
    }

    private function checkFolders($dirsList, $file, $folder, $paramProvider)
    {
        $fileCounts = [];
        foreach ($dirsList as $listName => $dirs) {
            $filePaths = Paths::getAbsFilePaths($dirs, $file, $folder);
            $this->checkFilePaths($filePaths, $paramProvider);

            $fileCounts[$listName] = [
                'paths' => $dirs,
                'fileCount' => count($filePaths),
            ];
        }

        return $fileCounts;
    }

    private function shouldRequestThanks(): bool
    {
        $currentCommandName = request()->server('argv')[1] ?? '';

        return random_int(1, 7) == 2 && Str::startsWith($currentCommandName, 'check:im');
    }

    private function getParamProvider()
    {
        return function ($tokens) {
            $imports = ParseUseStatement::parseUseStatements($tokens);

            return $imports[0] ?: [$imports[1]];
        };
    }

    private function getAutoloadFiles()
    {
        $paths = ComposerJson::readAutoloadFiles();

        return HandleErrors::getAbsoluteFilePaths($paths);
    }
}
