<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassAtMethod;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\ClassAtMethodHandler;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\PrintWrongClassRefs;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\AutoloadFiles;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasesCheck;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasReplacer;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasReporter;
use Imanghafoori\LaravelMicroscope\Features\Thanks;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\BladeFiles;
use Imanghafoori\LaravelMicroscope\Iterators\ChecksOnPsr4Classes;
use Imanghafoori\LaravelMicroscope\Iterators\ClassMapIterator;
use Imanghafoori\LaravelMicroscope\Iterators\FileIterators;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;
use JetBrains\PhpStorm\Pure;

class CheckImportsCommand extends Command
{
    use LogsErrors;

    protected $signature = 'check:imports
        {--force : fixes without asking}
        {--w|wrong : Only reports wrong imports}
        {--e|extra : Only reports extra imports}
        {--f|file= : Pattern for file names to scan}
        {--F|except-file= : Pattern for file names to avoid}
        {--D|except-folder= : Pattern for folder names to avoid}
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

        FacadeAliasesCheck::$command = $this->getOutput();

        if ($this->option('nofix')) {
            ClassAtMethodHandler::$fix = false;
            FacadeAliasesCheck::$handler = FacadeAliasReporter::class;
            CheckClassReferencesAreValid::$wrongClassRefsHandler = PrintWrongClassRefs::class;
        }

        if (file_exists($path = CachedFiles::getFolderPath().'check_imports.php')) {
            CheckClassReferencesAreValid::$cache = (require $path) ?: [];
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

        $pathDTO = PathFilterDTO::makeFromOption($this);

        $routeFiles = FilePath::removeExtraPaths(RoutePaths::get(), $pathDTO);
        $autoloadFiles = ComposerJson::autoloadedFilesList(base_path());

        foreach ($autoloadFiles as $path => $autoloadFile) {
            $autoloadFiles[$path] = FilePath::removeExtraPaths($autoloadFile, $pathDTO);
        }

        $paramProvider = self::getParamProvider();

        $checks = $this->checks;
        unset($checks[1]);

        $classMapStats = ClassMapIterator::iterate(base_path(), $checks, $paramProvider, $pathDTO);

        $routeFiles = FileIterators::checkFiles($routeFiles, $checks, $paramProvider);
        $autoloadedFilesGen = FileIterators::checkFilePaths($autoloadFiles, $checks, $paramProvider);

        $foldersStats = FileIterators::checkFolders(
            $checks,
            self::getLaravelFolders(),
            $paramProvider,
            $pathDTO
        );

        $psr4Stats = ForPsr4LoadedClasses::check($this->checks, $paramProvider, $pathDTO);

        $checks = $this->checks;
        unset($checks[3]); // avoid checking facades aliases in blade files.
        $bladeStats = BladeFiles::check($checks, $paramProvider, $pathDTO);

        $errorPrinter = ErrorPrinter::singleton($this->output);

        /**
         * @var string[] $messages
         */
        $messages = $this->getMessages($psr4Stats, $classMapStats, $bladeStats, $foldersStats, $routeFiles, $autoloadedFilesGen, $errorPrinter);

        Reporters\Psr4ReportPrinter::printMessages($messages, $this->getOutput());
        if (! ImportsAnalyzer::$checkedRefCount) {
            $messages = '<options=bold;fg=yellow>No imports were found!</> with filter: <fg=red>"'.($pathDTO->includeFile ?: $pathDTO->includeFolder).'"</>';
            $this->getOutput()->writeln($messages);
        }

        $this->finishCommand($errorPrinter);

        $errorPrinter->printTime();

        if (Thanks::shouldShow()) {
            self::printThanks($this);
        }

        if ($cache = CheckClassReferencesAreValid::$cache) {
            self::writeCacheContent($cache);
        }

        $this->line('');

        return ErrorCounter::getTotalErrors() > 0 ? 1 : 0;
    }

    private static function printThanks($command)
    {
        $command->line(PHP_EOL);
        foreach (Thanks::messages() as $msg) {
            $command->line($msg);
        }
    }

    #[Pure]
    private static function getParamProvider()
    {
        return function (PhpFileDescriptor $file) {
            $imports = ParseUseStatement::parseUseStatements($file->getTokens());

            return $imports[0] ?: [$imports[1]];
        };
    }

    #[Pure]
    private static function getFilesStats(): string
    {
        $filesCount = ChecksOnPsr4Classes::$checkedFilesCount;

        return $filesCount ? CheckImportReporter::getFilesStats($filesCount) : '';
    }

    /**
     * @return array<string, \Generator>
     */
    #[Pure(true)]
    private static function getLaravelFolders()
    {
        return [
            'config' => LaravelPaths::configDirs(),
            'migrations' => LaravelPaths::migrationDirs(),
        ];
    }

    private static function writeCacheContent(array $cache): void
    {
        $folder = CachedFiles::getFolderPath();
        ! is_dir($folder) && mkdir($folder);
        $content = CachedFiles::getCacheFileContents($cache);
        $path = $folder.'check_imports.php';
        file_exists($path) && chmod($path, 0777);
        file_put_contents($path, $content);
    }

    private function getMessages(
        $psr4Stats,
        $classMapStats,
        $bladeStats,
        $foldersStats,
        $routeFiles,
        $autoloadedFilesGen,
        ErrorPrinter $errorPrinter
    ) {
        yield CheckImportReporter::totalImportsMsg();
        yield Reporters\Psr4Report::getPresentations($psr4Stats, $classMapStats, $autoloadedFilesGen);
        yield PHP_EOL.CheckImportReporter::header();
        yield PHP_EOL.self::getFilesStats();
        yield PHP_EOL.Reporters\BladeReport::getBladeStats($bladeStats).PHP_EOL;
        yield Reporters\LaravelFoldersReport::foldersStats($foldersStats);
        yield CheckImportReporter::getRouteStats($routeFiles);
        yield PHP_EOL.Reporters\SummeryReport::summery($errorPrinter->errorsList);
    }
}
