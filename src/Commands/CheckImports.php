<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\CheckBladeFiles;
use Imanghafoori\LaravelMicroscope\CheckClasses;
use Imanghafoori\LaravelMicroscope\FileReaders\ConfigPaths;
use Imanghafoori\LaravelMicroscope\Checks\CheckClassReferences;
use Imanghafoori\LaravelMicroscope\Contracts\FileCheckContract;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Traits\ScansFiles;

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

        $psr4 = ComposerJson::readKey('autoload.psr-4');

        $this->checkFilePaths(RoutePaths::get());

        $this->checkFilePaths(ConfigPaths::get());

        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            try {
                $files = FilePath::getAllPhpFiles($psr4Path);
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

        // checks the blade files for class references.
        CheckBladeFiles::applyChecks([
            [CheckClassReferences::class, 'check'],
        ]);

        $this->checkConfig();

        $this->finishCommand($errorPrinter);
    }

    protected function checkConfig()
    {
        $user = config('auth.providers.users.model');
        if (! $user || ! class_exists($user) || ! is_subclass_of($user, Model::class)) {
            resolve(ErrorPrinter::class)->authConf();
        }
    }

    protected function warnDumping($msg)
    {
        $this->info('It seems composer has some trouble with autoload...');
        $this->info($msg);
        $this->info('Running "composer dump-autoload" command...');
    }

    private function checkFilePaths($paths)
    {
        foreach($paths as $path) {
            $tokens = token_get_all(file_get_contents($path));
            CheckClassReferences::check($tokens, $path);
            CheckClasses::checkAtSignStrings($tokens, $path, true);
        }
    }
}
