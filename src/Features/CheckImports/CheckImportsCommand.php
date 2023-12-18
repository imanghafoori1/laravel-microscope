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
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasesCheck;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasReplacer;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasReporter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Iterators\ChecksOnPsr4Classes;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
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

    protected $customMsg = 'All imports are Correct! \(^_^)/';

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
        $folder = rtrim($folder, '/\\');

        $routeFiles = FilePath::removeExtraPaths(RoutePaths::get(), $fileName, $folder);
        $classMapFiles = FilePath::removeExtraPaths(ComposerJson::getClassMaps(base_path()), $fileName, $folder);
        $autoloadedFiles = FilePath::removeExtraPaths(ComposerJson::autoloadedFilesList(base_path()), $fileName, $folder);

        $paths = array_merge($classMapFiles, $autoloadedFiles, $routeFiles);

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

        $checkedFilesCount = ChecksOnPsr4Classes::$checkedFilesCount;
        $errorPrinter = ErrorPrinter::singleton($this->output);
        $this->finishCommand($errorPrinter);
        ErrorCounter::$errors = $errorPrinter->errorsList;

        $messages = [];
        $messages[] = CheckImportReporter::totalImportsMsg(ImportsAnalyzer::$checkedRefCount);
        $messages[] = CheckImportReporter::printPsr4($psr4Stats);
        $messages[] = CheckImportReporter::header();
        $checkedFilesCount && $messages[] = CheckImportReporter::getFilesStats($checkedFilesCount);
        $bladeStats && $messages[] = CheckImportReporter::getBladeStats($bladeStats, BladeFiles::$checkedFilesCount);
        $foldersStats && $messages[] = CheckImportReporter::foldersStats($foldersStats);
        $messages[] = CheckImportReporter::getRouteStats(count($routeFiles));
        $messages[] = CheckImportReporter::formatErrorSummary(ErrorCounter::getTotalErrors(), ImportsAnalyzer::$checkedRefCount);
        $messages[] = CheckImportReporter::format('unused import', ErrorCounter::getExtraImportsCount());
        $messages[] = CheckImportReporter::format('wrong import', ErrorCounter::getExtraWrongCount());
        $messages[] = CheckImportReporter::format('wrong class reference', ErrorCounter::getWrongUsedClassCount());

        $this->getOutput()->writeln(implode(PHP_EOL, array_filter($messages)));

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

    /**
     * @param $dirsList
     * @param $paramProvider
     * @param $file
     * @param $folder
     * @return array<string, array<string, array<string, array<int, string>>>>
     */
    private function checkFolders($dirsList, $paramProvider, $file, $folder)
    {
        $files = [];
        foreach ($dirsList as $listName => $dirs) {
            $filePaths = Paths::getAbsFilePaths($dirs, $file, $folder);
            $this->checkFilePaths($filePaths, $paramProvider);

            foreach ($filePaths as $dir => $filePathList) {
                $files[$listName][$dir] = $filePathList;
            }
        }

        return $files;
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

    private function getParamProvider()
    {
        return function ($tokens) {
            $imports = ParseUseStatement::parseUseStatements($tokens);

            return $imports[0] ?: [$imports[1]];
        };
    }

    private function reportAll($psr4Stats, $foldersStats, $bladeStats, $routeCounts, $errors)
    {
    }

    private function getLaravelFolders()
    {
        return [
            'config' => LaravelPaths::configDirs(),
            'migrations' => LaravelPaths::migrationDirs(),
        ];
    }
}
