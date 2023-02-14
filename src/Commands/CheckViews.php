<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\Checks\CheckView;
use Imanghafoori\LaravelMicroscope\Checks\CheckViewFilesExistence;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;

class CheckViews extends Command
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
            FilePath::removeExtraPaths(RoutePaths::get(), $fileName, $folder)
        );
        ForPsr4LoadedClasses::check([CheckView::class], [], $fileName, $folder);
        $this->checkBladeFiles();

        $this->getOutput()->writeln(' - '.CheckView::$checkedCallsNum.' view references were checked to exist. ('.CheckView::$skippedCallsNum.' skipped)');
        event('microscope.finished.checks', [$this]);

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

    private function checkBladeFiles()
    {
        BladeFiles::check([CheckViewFilesExistence::class]);
    }
}
