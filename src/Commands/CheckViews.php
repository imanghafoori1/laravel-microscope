<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\Checks\CheckView;
use Imanghafoori\LaravelMicroscope\Checks\CheckViewFilesExistence;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;

class CheckViews extends Command
{
    public static $checkedCallsNum = 0;

    public static $skippedCallsNum = 0;

    protected $signature = 'check:views {--detailed : Show files being checked} {--f|file=} {--d|folder=}';

    protected $description = 'Checks the validity of blade files';

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Checking views...');

        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');

        $errorPrinter->printer = $this->output;
        $this->checkRoutePaths($fileName, $folder);
        ForPsr4LoadedClasses::check([CheckView::class], [], $fileName, $folder);
        $this->checkBladeFiles();

        $this->getOutput()->writeln(' - '.self::$checkedCallsNum.' view references were checked to exist. ('.self::$skippedCallsNum.' skipped)');
        event('microscope.finished.checks', [$this]);

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private function checkForViewMake($absPath, $staticCalls)
    {
        $tokens = \token_get_all(\file_get_contents($absPath));

        CheckView::checkViewCalls($tokens, $absPath, $staticCalls);
    }

    private function checkRoutePaths($fileName, $folder)
    {
        foreach (RoutePaths::get($fileName, $folder) as $filePath) {
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
