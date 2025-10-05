<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\LaravelFoldersReport;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Psr4ReportPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassAtMethod;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\ClassAtMethodHandler;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\PrintWrongClassRefs;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\RouteReport;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasesCheck;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasReplacer;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasReporter;
use Imanghafoori\LaravelMicroscope\Features\Thanks;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ChecksOnPsr4Classes;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedClassMaps;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedFiles;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedPsr4Classes;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use Imanghafoori\LaravelMicroscope\Iterators\ForFolderPaths;
use Imanghafoori\LaravelMicroscope\Iterators\ForRouteFiles;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
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
     * @var array<int, class-string<\Imanghafoori\LaravelMicroscope\Check>>
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

        $useStatementParser = [self::useStatementParser()];

        FacadeAliasesCheck::$importsProvider = self::useStatementParser();
        $checks = $this->checks;
        unset($checks[1]);

        $checkSet = CheckSet::init($checks, $pathDTO, $useStatementParser);
        $routeFiles = RouteReport::getStats(ForRouteFiles::check($checkSet));
        $psr4Stats = ForAutoloadedPsr4Classes::check(
            CheckSet::init($this->checks, $pathDTO, $useStatementParser)
        );

        $classMapStats = ForAutoloadedClassMaps::check($checkSet);
        $autoloadedFilesStats = ForAutoloadedFiles::check($checkSet);
        $foldersStats = LaravelFoldersReport::formatFoldersStats(ForFolderPaths::check($checkSet, LaravelPaths::getMigrationConfig()));
        $checks = $this->checks;
        unset($checks[3]); // avoid checking facades aliases in blade files.
        $checkSet->setChecks($checks);
        $bladeStats = Reporters\BladeReport::getBladeStats(ForBladeFiles::check($checkSet));

        $errorPrinter = ErrorPrinter::singleton($this->output);

        $messages = Reporters\Psr4Report::formatAutoloads($psr4Stats, $classMapStats, $autoloadedFilesStats);
        /**
         * @var string[] $messages
         */
        $messages = self::addOtherMessages($messages, $bladeStats, $foldersStats, $routeFiles);

        Psr4ReportPrinter::printAll($messages, $this->getOutput());
        // must be after other messages:
        Psr4ReportPrinter::printAll([PHP_EOL.Reporters\SummeryReport::summery($errorPrinter->errorsList)], $this->getOutput());
        if (! ImportsAnalyzer::$checkedRefCount) {
            $messages = '<options=bold;fg=yellow>No imports were found!</> with filter: <fg=red>"'.($pathDTO->includeFile ?: $pathDTO->includeFolder).'"</>';
            $this->getOutput()->writeln($messages);
        }

        $this->finishCommand($errorPrinter);

        $errorPrinter->printTime();

        Thanks::shouldShow() && self::printThanks($this);

        if ($cache = CheckClassReferencesAreValid::$cache) {
            self::writeCacheContent($cache);
        }
        CachedFiles::writeCacheFiles();

        $this->line('');

        return ErrorCounter::getTotalErrors() > 0 ? 1 : 0;
    }

    private static function printThanks($command)
    {
        $command->line(PHP_EOL);
        Loop::over(Thanks::messages(), fn ($msg) => $command->line($msg));
    }

    #[Pure]
    private static function useStatementParser()
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

    private static function writeCacheContent(array $cache): void
    {
        $folder = CachedFiles::getFolderPath();
        ! is_dir($folder) && mkdir($folder);
        $content = CachedFiles::getCacheFileContents($cache);
        $path = $folder.'check_imports.php';
        file_exists($path) && chmod($path, 0777);
        file_put_contents($path, $content);
    }

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\AutoloadStats  $autoloadStats
     * @param  array<string, \Generator<string, int>>  $bladeStats
     * @param  array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto>  $foldersStats
     * @param  \Generator<int, PhpFileDescriptor>  $routeFiles
     * @return array
     */
    private static function addOtherMessages($autoloadStats, $bladeStats, $foldersStats, $routeFiles)
    {
        return [
            CheckImportReporter::totalImportsMsg(),
            $autoloadStats,
            PHP_EOL.CheckImportReporter::header(),
            PHP_EOL.self::getFilesStats(),
            PHP_EOL.$bladeStats.PHP_EOL,
            $foldersStats,
            $routeFiles,
        ];
    }
}
