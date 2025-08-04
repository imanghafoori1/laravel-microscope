<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDD;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\LaravelFoldersReport;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4ReportPrinter;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\ClassMapIterator;
use Imanghafoori\LaravelMicroscope\Iterators\FileIterators;
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

        $paramProvider = function (PhpFileDescriptor $file, $token) {
            ErrorPrinter::singleton()->simplePendError(
                $token[1], $file->getAbsolutePath(), $token[2], 'ddFound', 'Debug function found: '
            );
        };

        $psr4Stats = ForPsr4LoadedClasses::check([CheckDD::class], [$paramProvider], $pathDTO);
        $classMapStats = ClassMapIterator::iterate(base_path(), [CheckDD::class], [$paramProvider], $pathDTO);

        $foldersStats = FileIterators::checkFolders(
            [CheckDD::class], $this->getLaravelFolders(), [$paramProvider], $pathDTO
        );

        Psr4Report::printAutoload($psr4Stats, $classMapStats, $this->getOutput());
        $messages = LaravelFoldersReport::foldersStats($foldersStats);
        Psr4ReportPrinter::printMessages($messages, $this->getOutput());
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
