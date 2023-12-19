<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use DateInterval;
use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassAtMethod;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\ClassAtMethodHandler;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\PrintWrongClassRefs;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasesCheck;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasReplacer;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasReporter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Iterators\ChecksOnPsr4Classes;
use Imanghafoori\LaravelMicroscope\Iterators\FileIterators;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;
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

    protected $customMsg = '';

    /**
     * @var array<int, class-string<\Imanghafoori\LaravelMicroscope\Iterators\Check>>
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

        $this->handleOptions();

        list($fileName, $folder, $routeFiles, $paths) = $this->aggregateFilePaths();

        $paramProvider = $this->getParamProvider();

        $checks = $this->checks;
        unset($checks[1]);

        FileIterators::checkFilePaths($paths, $paramProvider, $checks);

        $foldersStats = FileIterators::checkFolders(
            FileIterators::getLaravelFolders(),
            $paramProvider,
            $fileName,
            $folder,
            $checks
        );

        $psr4Stats = ForPsr4LoadedClasses::check($this->checks, $paramProvider, $fileName, $folder);
        $bladeStats = BladeFiles::check($this->checks, $paramProvider, $fileName, $folder);

        $filesCount = ChecksOnPsr4Classes::$checkedFilesCount;
        $bladeCount = array_sum($bladeStats);
        $refCount = ImportsAnalyzer::$checkedRefCount;
        $errorPrinter = ErrorPrinter::singleton($this->output);
        $this->finishCommand($errorPrinter);

        $messages = [];
        $messages[] = Reporters\CheckImportReporter::totalImportsMsg($refCount);
        $messages[] = Reporters\Psr4Report::printPsr4($psr4Stats);
        $messages[] = CheckImportReporter::header();
        $filesCount && $messages[] = Reporters\CheckImportReporter::getFilesStats($filesCount);
        $bladeCount && $messages[] = Reporters\BladeReport::getBladeStats($bladeStats, $bladeCount);
        $messages[] = Reporters\LaravelFoldersReport::foldersStats($foldersStats);
        count($routeFiles) && $messages[] = CheckImportReporter::getRouteStats(count($routeFiles));
        $messages[] = Reporters\SummeryReport::summery($errorPrinter->errorsList);

        if (! $refCount) {
            $messages = ['<options=bold;fg=yellow>No imports were found!</> with filter: <fg=red>"'.($fileName ?: $folder).'"</>'];
        }

        $this->getOutput()->writeln(implode(PHP_EOL, array_filter($messages)));

        $errorPrinter->printTime();

        if ($this->shouldRequestThanks()) {
            ErrorPrinter::thanks($this);
        }

        $this->line('');

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private function shouldRequestThanks(): bool
    {
        $key = 'microscope_thanks_throttle';

        if (cache()->get($key)) {
            return false;
        }

        // $currentCommandName = request()->server('argv')[1] ?? '';
        $show = random_int(1, 5) === 2;
        $show && cache()->set($key, '_', DateInterval::createFromDateString('3 days'));

        return $show;
    }

    /**
     * @return \Closure
     */
    private function getParamProvider()
    {
        return function ($tokens) {
            $imports = ParseUseStatement::parseUseStatements($tokens);

            return $imports[0] ?: [$imports[1]];
        };
    }

    /**
     *  Handles command options and sets corresponding flags and handlers.
     *  This method processes the input options provided to the command and sets up necessary handlers and flags
     *  based on these options.
     *
     * @return void
     */
    private function handleOptions(): void
    {
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
    }

    /**
     *  Aggregates file paths and related information based on command options.
     *
     *  This method prepares and returns essential data for file path processing. It handles the extraction and formatting
     *  of file names and folders from the command options, and compiles a list of file paths to be checked.
     *
     * @return array An array containing:
     *                - string $fileName: The file name pattern provided in the command options.
     *                - string $folder: The folder pattern provided in the command options.
     *                - array $routeFiles: List of route file paths after applying filters.
     *                - array $paths: The aggregated list of file paths including class maps, autoloaded files, and route files.
     */
    private function aggregateFilePaths(): array
    {
        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');
        $folder = rtrim($folder, '/\\');

        $routeFiles = FilePath::removeExtraPaths(RoutePaths::get(), $fileName, $folder);
        $classMapFiles = FilePath::removeExtraPaths(ComposerJson::getClassMaps(base_path()), $fileName, $folder);
        $autoloadedFiles = FilePath::removeExtraPaths(ComposerJson::autoloadedFilesList(base_path()), $fileName, $folder);

        $paths = array_merge($classMapFiles, $autoloadedFiles, $routeFiles);
        return array($fileName, $folder, $routeFiles, $paths);
    }
}
