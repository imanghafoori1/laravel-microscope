<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Psr4Classes;
use Imanghafoori\LaravelMicroscope\CheckClasses;
use Imanghafoori\LaravelMicroscope\CheckBladeFiles;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Traits\ScansFiles;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Checks\CheckClassReferences;
use Imanghafoori\LaravelMicroscope\Contracts\FileCheckContract;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckImports extends Command implements FileCheckContract
{
    use LogsErrors;

    use ScansFiles;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:imports {--d|detailed : Show files being checked}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the validity of use statements';

    /**
     * Execute the console command.
     *
     * @param  ErrorPrinter  $errorPrinter
     *
     * @throws \ErrorException
     * @return mixed
     */
    public function handle(ErrorPrinter $errorPrinter)
    {
        $t1 = microtime(true);
        $this->info('Checking imports...');

        $errorPrinter->printer = $this->output;

        $this->checkFilePaths(RoutePaths::get());
        $this->checkFilePaths(Paths::getPathsList(app()->configPath()));
        $this->checkFilePaths(Paths::getPathsList(app()->databasePath()));
        Psr4Classes::check([CheckClasses::class]);

        // checks the blade files for class references.
        CheckBladeFiles::applyChecks([CheckClassReferences::class]);

        $this->finishCommand($errorPrinter);
        $this->info('Total elapsed time:'.((microtime(true) - $t1)).' seconds');
    }

    private function checkFilePaths($paths)
    {
        foreach($paths as $path) {
            $tokens = token_get_all(file_get_contents($path));
            CheckClassReferences::check($tokens, $path);
            CheckClasses::checkAtSignStrings($tokens, $path, true);
        }
    }

    private function checkPsr4()
    {
        $psr4 = ComposerJson::readKey('autoload.psr-4');
        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = Paths::getPathsList(base_path($psr4Path));
            foreach ($files as $absFilePath) {
                $tokens = token_get_all(file_get_contents($absFilePath));
                CheckClasses::check($tokens, $absFilePath);
            }
        }
    }
}
