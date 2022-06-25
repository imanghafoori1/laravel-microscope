<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\Checks\FacadeAliases;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckAliases extends Command
{
    use LogsErrors;

    protected $signature = 'check:aliases {--f|file=} {--d|folder=} {--detailed : Show files being checked} {--s|nofix : avoids the automatic fixes}';

    protected $description = 'Replaces facade aliases with full namespace';

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Checking Aliases...');

        $this->option('nofix') && config(['microscope.no_fix' => true]);

        $errorPrinter->printer = $this->output;

        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');
        FacadeAliases::$command = $this;

        ForPsr4LoadedClasses::check([FacadeAliases::class], [], $fileName, $folder);

        $this->finishCommand($errorPrinter);

        $errorPrinter->printTime();

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private function checkFilePaths($paths)
    {
        foreach ($paths as $path) {
            $tokens = token_get_all(file_get_contents($path));
            CheckClassReferences::check($tokens, $path);
            CheckClassReferencesAreValid::checkAtSignStrings($tokens, $path, true);
        }
    }

    private function checkFolders($dirs, $file, $folder)
    {
        foreach ($dirs as $dir) {
            $this->checkFilePaths(Paths::getAbsFilePaths($dir, $file, $folder));
        }
    }
}
