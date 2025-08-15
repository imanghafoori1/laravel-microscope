<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDD;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\LaravelFoldersReport;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Psr4ReportPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedClassMaps;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedFiles;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedPsr4Classes;
use Imanghafoori\LaravelMicroscope\Iterators\ForFolderPaths;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use JetBrains\PhpStorm\Pure;

class CheckDDCommand extends Command
{
    protected $signature = 'check:dd
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
';

    protected $description = 'Checks the debug functions.';

    public function handle()
    {
        event('microscope.start.command');
        $this->info('Checking dd...');

        $pathDTO = PathFilterDTO::makeFromOption($this);

        $onErrorCallback = function (PhpFileDescriptor $file, $token) {
            ErrorPrinter::singleton()->simplePendError(
                $token[1], $file->getAbsolutePath(), $token[2], 'ddFound', 'Debug function found: '
            );
        };

        $psr4Stats = ForAutoloadedPsr4Classes::check([CheckDD::class], [$onErrorCallback], $pathDTO);
        $classMapStats = ForAutoloadedClassMaps::check(base_path(), [CheckDD::class], [$onErrorCallback], $pathDTO);
        $autoloadedFilesStats = ForAutoloadedFiles::check(base_path(), [CheckDD::class], [$onErrorCallback], $pathDTO);

        $foldersStatsData = ForFolderPaths::checkFolders(
            [CheckDD::class], $this->getLaravelFolders(), [$onErrorCallback], $pathDTO
        );

        $lines = Psr4Report::getConsoleMessages($psr4Stats, $classMapStats, $autoloadedFilesStats);
        Psr4ReportPrinter::printAll($lines, $this->getOutput());
        $messages = LaravelFoldersReport::formatFoldersStats($foldersStatsData);
        Psr4ReportPrinter::printAll($messages, $this->getOutput());
        CachedFiles::writeCacheFiles();

        $this->getOutput()->writeln(' - Finished looking for debug functions.');

        event('microscope.finished.checks', [$this]);

        return app(ErrorPrinter::class)->hasErrors() ? 1 : 0;
    }

    /**
     * @return array<string, \Generator>
     */
    #[Pure(true)]
    private function getLaravelFolders()
    {
        return [
            'config' => LaravelPaths::configDirs(),
            'migrations' => LaravelPaths::migrationDirs(),
        ];
    }
}
