<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDD;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\LaravelFoldersReport;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Psr4ReportPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\ForComposerJsonFiles;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForFolderPaths;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;

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
        CheckDD::$onErrorCallback = $onErrorCallback;
        $checkSet = CheckSet::init([CheckDD::class], $pathDTO);

        $lines = ForComposerJsonFiles::checkAndPrint($checkSet);

        $foldersStats = ForFolderPaths::check($checkSet, LaravelPaths::getMigrationConfig());

        $lines->add(LaravelFoldersReport::formatFoldersStats($foldersStats));

        Psr4ReportPrinter::printAll($lines, $this->getOutput());
        CachedFiles::writeCacheFiles();

        $this->getOutput()->writeln(' - Finished looking for debug functions.');

        event('microscope.finished.checks', [$this]);

        return app(ErrorPrinter::class)->hasErrors() ? 1 : 0;
    }
}
