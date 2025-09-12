<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraFQCN;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\LaravelFoldersReport;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Psr4ReportPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\ChecksOnPsr4Classes;
use Imanghafoori\LaravelMicroscope\Iterators\DTO\CheckCollection;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedClassMaps;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedFiles;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedPsr4Classes;
use Imanghafoori\LaravelMicroscope\Iterators\ForFolderPaths;
use Imanghafoori\LaravelMicroscope\Iterators\ForRouteFiles;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;
use JetBrains\PhpStorm\Pure;

class CheckExtraFQCNCommand extends Command
{
    use LogsErrors;

    protected $signature = 'check:fqcn
        {--fix : Fix references}
        {--class= : Fix references of the specified class}
        {--f|file= : Pattern for file names to scan}
        {--d|folder= : Pattern for file names to scan}
        {--F|except-file= : Pattern for file names to avoid}
        {--D|except-folder= : Pattern for folder names to avoid}';

    protected $description = 'Checks for unnecessary FQCNs.';

    protected $customMsg = 'No Unnecessary Fully Qualified Class Name found.  \(^_^)/';

    public function handle()
    {
        event('microscope.start.command');
        $this->line('');
        $this->info('Checking class references...');

        $pathDTO = PathFilterDTO::makeFromOption($this);

        $fix = $this->option('fix');
        $class = $this->option('class');

        $useStatementParser = [self::useStatementParser(), $fix, $class];

        $checks = CheckCollection::make([ExtraFQCN::class]);

        $routeFiles = ForRouteFiles::check($checks, $pathDTO, $useStatementParser);
        $classMapStats = ForAutoloadedClassMaps::check(base_path(), $checks, $pathDTO, $useStatementParser);
        $autoloadedFilesStats = ForAutoloadedFiles::check(base_path(), $checks, $pathDTO, $useStatementParser);
        $psr4Stats = ForAutoloadedPsr4Classes::check($checks, $pathDTO, $useStatementParser);
        $foldersStats = ForFolderPaths::check($checks, LaravelPaths::getMigrationConfig(), $useStatementParser, $pathDTO);

        $messages = self::addOtherMessages(
            Psr4Report::formatAutoloads($psr4Stats, $classMapStats, $autoloadedFilesStats),
            $foldersStats,
            $routeFiles
        );

        Psr4ReportPrinter::printAll($messages, $this->getOutput());

        $errorPrinter = ErrorPrinter::singleton($this->output);
        $this->finishCommand($errorPrinter);
        $errorPrinter->printTime();

        CachedFiles::writeCacheFiles();

        ! $fix && self::hasError() && $this->printGuide();

        return self::hasError() > 0 ? 1 : 0;
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

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\AutoloadStats  $autoloadStats
     * @param  array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto>  $foldersStats
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\FilesDto  $routeFiles
     * @return array
     */
    private static function addOtherMessages($autoloadStats, $foldersStats, $routeFiles)
    {
        return [
            CheckImportReporter::totalImportsMsg(),
            $autoloadStats,
            PHP_EOL.CheckImportReporter::header(),
            PHP_EOL.self::getFilesStats(),
            LaravelFoldersReport::formatFoldersStats($foldersStats),
            CheckImportReporter::getRouteStats($routeFiles),
        ];
    }

    private function printGuide()
    {
        $this->line('<fg=yellow> You may use `--fix` option to delete extra code:</>');
        $this->line('<fg=yellow> php artisan check:fqcn --fix</>');
    }

    private static function hasError()
    {
        return isset(ErrorPrinter::singleton()->errorsList['FQCN']);
    }
}
