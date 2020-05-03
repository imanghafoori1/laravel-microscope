<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
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
        $this->info('Checking imports...');

        $errorPrinter->printer = $this->output;

        $this->checkFilePaths(RoutePaths::get());
        $this->checkFilePaths(Paths::getPathsList(app()->configPath()));
        $this->checkFilePaths(Paths::getPathsList(app()->databasePath()));
        $this->checkPsr4();

        // checks the blade files for class references.
        CheckBladeFiles::applyChecks([
            [CheckClassReferences::class, 'check'],
        ]);

        $this->finishCommand($errorPrinter);
    }

    protected function warnDumping($msg)
    {
        $this->info('It seems composer has some trouble with autoload...');
        $this->info($msg);
        $this->info('Running "composer dump-autoload" command...  \(*_*)\  ');
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
            try {
                $files = Paths::getPathsList(base_path($psr4Path));
                CheckClasses::checkImports($files, $this);
            } catch (\ErrorException $e) {
                // In case a file is moved or deleted...
                // composer will need a dump autoload.
                if (! Str::endsWith($e->getFile(), 'vendor\composer\ClassLoader.php')) {
                    throw $e;
                }

                $this->warnDumping($e->getMessage());
                resolve(Composer::class)->dumpAutoloads();
            }
        }
    }
}
