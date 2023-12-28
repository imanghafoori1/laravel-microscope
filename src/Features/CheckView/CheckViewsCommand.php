<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckView;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckView\Check\CheckView;
use Imanghafoori\LaravelMicroscope\Features\CheckView\Check\CheckViewFilesExistence;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Iterators\BladeFiles;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;

class CheckViewsCommand extends Command
{
    protected $signature = 'check:views {--detailed : Show files being checked} {--f|file=} {--d|folder=}';

    protected $description = 'Checks the validity of blade files';

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Checking views...');

        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');

        $errorPrinter->printer = $this->output;
        $this->checkRoutePaths(
            FilePath::removeExtraPaths(RoutePaths::get(), $folder, $fileName)
        );
        $this->checkPsr4($fileName, $folder);
        $this->checkBladeFiles($fileName, $folder);

        $this->logErrors($errorPrinter);
        $this->getOutput()->writeln($this->stats(
            CheckView::$checkedCallsCount,
            CheckView::$skippedCallsCount
        ));

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private function checkForViewMake($absPath, $staticCalls)
    {
        $tokens = \token_get_all(\file_get_contents($absPath));

        CheckView::checkViewCalls($tokens, $absPath, $staticCalls);
    }

    private function checkRoutePaths($paths)
    {
        foreach ($paths as $filePath) {
            $this->checkForViewMake($filePath, [
                'View' => ['make', 0],
                'Route' => ['view', 1],
            ]);
        }
    }

    private function checkBladeFiles($fileName, $folder)
    {
        iterator_to_array(BladeFiles::check([CheckViewFilesExistence::class], null, $fileName, $folder));
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

    private function checkPsr4(string $fileName, string $folder)
    {
        ForPsr4LoadedClasses::checkNow([CheckView::class], [], $fileName, $folder);
    }
}
