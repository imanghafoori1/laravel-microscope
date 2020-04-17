<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\LaravelMicroscope\CheckViews;
use Imanghafoori\LaravelMicroscope\CheckClasses;
use Imanghafoori\LaravelMicroscope\Analyzers\Util;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Traits\ScansFiles;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\MethodParser;
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
        $this->info('Checking imports ...');

        $errorPrinter->printer = $this->output;

        $psr4 = Util::parseComposerJson('autoload.psr-4');

        $this->getApplicationProviders($psr4);
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

        (new CheckViews)->check([
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

    private function getApplicationProviders($psr4)
    {
        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            foreach (config('app.providers') as $provider) {
                if (! Str::startsWith($provider, $psr4Namespace)) {
                    continue;
                }

                $absPath = $this->getFileAbsPath($psr4Namespace, $psr4Path, $provider).'.php';
                $tokens = token_get_all(file_get_contents($absPath));
                $methodCalls = MethodParser::extractParametersValueWithinMethod($tokens, ['loadRoutesFrom']);

                foreach ($methodCalls as $calls) {
                    $namespace = trim(str_replace(class_basename($provider), '', $provider), '\\');
                    $dir = (str_replace($psr4Namespace, $psr4Path, $namespace));
                    $dir = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $dir);

                    $firstParam = str_replace(["'", '"'], '', $calls['params'][0]);
                    $firstParam = str_replace('__DIR__.', $dir, $firstParam);
                    $filePath = FilePath::normalize($firstParam);
                    $tokens = token_get_all(file_get_contents($filePath));

                    CheckClassReferences::check($tokens, $filePath);
                    CheckClasses::checkAtSignStrings($tokens, $filePath);
                }
            }
        }
    }

    /**
     * @param $psr4Namespace
     * @param $psr4Path
     * @param $provider
     *
     * @return string
     */
    private function getFileAbsPath($psr4Namespace, $psr4Path, $provider)
    {
        return base_path(str_replace($psr4Namespace, $psr4Path, $provider));
    }
}
