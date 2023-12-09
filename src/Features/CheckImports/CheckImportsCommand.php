<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassAtMethod;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\ClassAtMethodHandler;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\PrintWrongClassRefs;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasesCheck;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasReplacer;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasReporter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
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
        {--e|extra : Only reports extra imports}
        {--f|file= : Pattern for file names to scan}
        {--d|folder= : Pattern for file names to scan}
        {--s|nofix : avoids the automatic fixes}
    ';

    protected $description = 'Checks the validity of use statements';

    protected $customMsg = 'All imports are Correct! \(^_^)/';

    /**
     * @var string[]
     */
    private $checks = [
        1 => CheckClassAtMethod::class,
        2 => CheckClassReferencesAreValid::class,
        3 => FacadeAliasesCheck::class,
    ];

    public function handle()
    {
        event('microscope.start.command');
        $this->line('');
        $this->info('Checking imports and class references...');

        FacadeAliasesCheck::$command = $this->getOutput();

        if ($this->option('nofix')) {
            ClassAtMethodHandler::$fix = false;
            FacadeAliasesCheck::$handler = FacadeAliasReporter::class;
            CheckClassReferencesAreValid::$wrongClassRefsHandler = PrintWrongClassRefs::class;
        }

        if ($this->option('force')) {
            FacadeAliasReplacer::$forceReplace = true;
        }

        if ($this->option('wrong')) {
            CheckClassReferencesAreValid::$checkExtra = false;
            unset($this->checks[3]); // avoid checking facades
        }

        if ($this->option('extra')) {
            CheckClassReferencesAreValid::$checkWrong = false;
            unset($this->checks[3]); // avoid checking facades
        }

        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');

        $paths = array_merge(
            ComposerJson::getClassMaps(base_path()),
            ComposerJson::autoloadedFilesList(base_path()),
            $routeFiles = RoutePaths::get()
        );

        $paths = FilePath::removeExtraPaths(
            $paths,
            $fileName,
            $folder
        );

        $paramProvider = $this->getParamProvider();
        $this->checkFilePaths($paths, $paramProvider);

        $foldersStats = $this->checkFolders(
            $this->getLaravelFolders(),
            $paramProvider,
            $fileName,
            $folder
        );

        $psr4Stats = ForPsr4LoadedClasses::check($this->checks, $paramProvider, $fileName, $folder);
        $bladeStats = BladeFiles::check($this->checks, $paramProvider, $fileName, $folder);

        $errorPrinter = ErrorPrinter::singleton($this->output);
        $this->finishCommand($errorPrinter);
        $this->reportAll($psr4Stats, $foldersStats, $bladeStats, count($routeFiles));

        $errorPrinter->printTime();

        if ($this->shouldRequestThanks()) {
            ErrorPrinter::thanks($this);
        }
        $this->line('');

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private function checkFilePaths($paths, $paramProvider)
    {
        $checks = $this->checks;
        unset($checks[1]);

        foreach ($paths as $dir => $absFilePaths) {
            foreach ((array) $absFilePaths as $absFilePath) {
                $tokens = token_get_all(file_get_contents($absFilePath));
                foreach ($checks as $check) {
                    $check::check($tokens, $absFilePath, $paramProvider($tokens));
                }
            }
        }
    }

    private function checkFolders($dirsList, $paramProvider, $file, $folder)
    {
        $fileCounts = [];
        foreach ($dirsList as $listName => $dirs) {
            $filePaths = Paths::getAbsFilePaths($dirs, $file, $folder);
            $this->checkFilePaths($filePaths, $paramProvider);

            foreach ($filePaths as $dir => $filePathList) {
                $fileCounts[$listName][$dir] = $filePathList;
            }
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

    private function reportAll($psr4Stats, $foldersStats, $bladeStats, $routeCounts)
    {
        $messages = CheckImportReporter::report($psr4Stats, $foldersStats, $bladeStats, $routeCounts);

        foreach ($messages as $message) {
            $this->getOutput()->writeln($message);
        }
    }

    private function getLaravelFolders()
    {
        return [
            'config' => LaravelPaths::configDirs(),
            'migrations' => LaravelPaths::migrationDirs(),
        ];
    }
}
