<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\CheckClasses;
use Imanghafoori\LaravelMicroscope\Checks\CheckClassReferences;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\Psr4Classes;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckImports extends Command
{
    use LogsErrors;

    protected $signature = 'check:imports {--d|detailed : Show files being checked} {--s|nofix : avoids the automatic fixes}';

    protected $description = 'Checks the validity of use statements';

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Checking imports...');

        $this->option('nofix') && config(['microscope.no_fix' => true]);

        $errorPrinter->printer = $this->output;

        $this->checkFilePaths(RoutePaths::get());
        $this->checkFilePaths(Paths::getAbsFilePaths(app()->configPath()));
        $this->checkFilePaths(Paths::getAbsFilePaths(app()->databasePath('seeds')));
        $this->checkFilePaths(Paths::getAbsFilePaths(LaravelPaths::migrationDirs()));
        $this->checkFilePaths(Paths::getAbsFilePaths(LaravelPaths::factoryDirs()));

        Psr4Classes::check([CheckClasses::class]);

        // checks the blade files for class references.
        BladeFiles::check([CheckClassReferences::class]);

        $this->finishCommand($errorPrinter);
        $this->getOutput()->writeln(' - '.CheckClassReferences::$refCount.' class references were checked within: '.Psr4Classes::$checkedFilesNum.' classes and '.BladeFiles::$checkedFilesNum.' blade files');

        $errorPrinter->printTime();

        if (random_int(1, 5) == 2 && Str::startsWith(request()->server('argv')[1] ?? '', 'check:im')) {
            ErrorPrinter::thanks($this);
        }

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private function checkFilePaths($paths)
    {
        foreach ($paths as $path) {
            $tokens = token_get_all(file_get_contents($path));
            CheckClassReferences::check($tokens, $path);
            CheckClasses::checkAtSignStrings($tokens, $path, true);
        }
    }
}
