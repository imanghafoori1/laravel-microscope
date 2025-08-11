<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckView;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\Features\CheckView\Check\CheckView;
use Imanghafoori\LaravelMicroscope\Features\CheckView\Check\CheckViewFilesExistence;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\ForAutoloadedPsr4Classes;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;

class CheckViewsCommand extends Command
{
    protected $signature = 'check:views {--detailed : Show files being checked}
        {--f|file=}
        {--d|folder=}
        {--F|except-file= : Comma seperated patterns for file names to avoid}
        {--D|except-folder= : Comma seperated patterns for folder names to avoid}
     ';

    protected $description = 'Checks the validity of blade files';

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Checking views...');

        $pathDTO = PathFilterDTO::makeFromOption($this);

        $errorPrinter->printer = $this->output;
        $this->checkRoutePaths(
            FilePath::removeExtraPaths(RoutePaths::get(), $pathDTO)
        );

        $psr4Stats = ForAutoloadedPsr4Classes::check([CheckView::class], [], $pathDTO);

        Psr4Report::formatAndPrintAutoload($psr4Stats, [], $this->getOutput());

        $this->checkBladeFiles($pathDTO);

        $this->logErrors($errorPrinter);
        $this->getOutput()->writeln($this->stats(
            CheckView::$checkedCallsCount,
            CheckView::$skippedCallsCount
        ));
        CachedFiles::writeCacheFiles();

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private function checkRoutePaths($paths)
    {
        foreach ($paths as $filePath) {
            CheckView::checkViewCalls(PhpFileDescriptor::make($filePath), [
                'View' => ['make', 0],
                'Route' => ['view', 1],
            ]);
        }
    }

    private function checkBladeFiles($pathDTO)
    {
        iterator_to_array(ForBladeFiles::check([CheckViewFilesExistence::class], null, $pathDTO));
    }

    private function stats($checkedCallsCount, $skippedCallsCount): string
    {
        return ' - '.$checkedCallsCount.' view references were checked to exist. ('.$skippedCallsCount.' skipped)';
    }

    private function logErrors(ErrorPrinter $errorPrinter)
    {
        if ($errorPrinter->hasErrors()) {
            $errorPrinter->logErrors();
        } else {
            $this->info('...'.PHP_EOL.'- All views are correct!');
        }

        $errorPrinter->printTime();
    }
}
