<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEnvCalls;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;

class CheckEnvCallsCommand extends BaseCommand
{
    protected $signature = 'check:bad_practices
        {--f|file= : Pattern for file names to scan}
        {--d|folder= : Pattern for file names to scan}
        {--F|except-file= : Comma seperated patterns for file names to exclude}
        {--D|except-folder= : Comma seperated patterns for folder names to exclude}
        ';

    protected $description = 'Checks for bad practices';

    public $checks = [EnvCallsCheck::class];

    public $initialMsg = 'Checking for env() calls outside config files...';

    public $customMsg = 'No env() function call was found.';

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        $pathDTO = PathFilterDTO::makeFromOption($this);

        $params = function ($name, $absPath, $lineNumber) {
            ErrorPrinter::singleton()->simplePendError(
                $name, $absPath, $lineNumber, 'envFound', 'env() function found: '
            );
        };

        $this->excludeConfigFiles($pathDTO);
        $this->checkPaths(LaravelPaths::getMigrationsFiles($pathDTO), $params);
        EnvCallsCheck::$onErrorCallback = $params;

        $iterator->printAll([
            $iterator->forComposerLoadedFiles(),
            $iterator->forBladeFiles(),
            PHP_EOL.$iterator->forRoutes(),
        ]);

        $this->info('&It is recommended use env() calls, only and only in config files.');
        $this->info('Otherwise you can NOT cache your config files using "config:cache"');
        $this->info('https://laravel.com/docs/5.5/configuration#configuration-caching');
    }

    /**
     * @param  \Generator|string[]|\Generator[]  $paths
     * @return void
     */
    private function checkPaths($paths, $params)
    {
        foreach ($paths as $filePath) {
            if (is_string($filePath)) {
                EnvCallsCheck::check(PhpFileDescriptor::make($filePath));
            } else {
                $this->checkPaths($filePath, $params);
            }
        }
    }

    private function excludeConfigFiles(PathFilterDTO $pathDTO)
    {
        $configs = Paths::getAbsFilePaths(LaravelPaths::configDirs(), PathFilterDTO::make());

        $configs = trim(implode(',', array_keys($configs)), ',');
        if ($pathDTO->excludeFolder) {
            $pathDTO->excludeFolder = $pathDTO->excludeFolder.','.$configs;
        } else {
            $pathDTO->excludeFolder = $configs;
        }
    }
}
