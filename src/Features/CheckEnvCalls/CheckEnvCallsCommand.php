<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEnvCalls;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Psr4ReportPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedFiles;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;

class CheckEnvCallsCommand extends Command
{
    protected $signature = 'check:bad_practices
        {--f|file= : Pattern for file names to scan}
        {--d|folder= : Pattern for file names to scan}
        {--F|except-file= : Comma seperated patterns for file names to exclude}
        {--D|except-folder= : Comma seperated patterns for folder names to exclude}
        ';

    protected $description = 'Checks for bad practices';

    public function handle()
    {
        event('microscope.start.command');
        $this->info('Checking for env() calls outside config files...');

        $pathDTO = PathFilterDTO::makeFromOption($this);

        $paths = LaravelPaths::getMigrationsFiles($pathDTO);

        $params = function ($name, $absPath, $lineNumber) {
            ErrorPrinter::singleton()->simplePendError(
                $name, $absPath, $lineNumber, 'envFound', 'env() function found: '
            );
        };

        $this->checkPaths(RoutePaths::get(), $params);

        foreach ($paths as $path) {
            $this->checkPaths($path, $params);
        }

        $psr4Stats = $this->checkPsr4Classes($pathDTO, $params);
        $autoloadedFilesStats = ForAutoloadedFiles::check(base_path(), [EnvCallsCheck::class], [$params], $pathDTO);
        $lines = Psr4Report::getPresentations($psr4Stats, [], $autoloadedFilesStats);
        Psr4ReportPrinter::printAll($lines, $this->getOutput());

        CachedFiles::writeCacheFiles();

        event('microscope.finished.checks', [$this]);
        $this->info('&It is recommended use env() calls, only and only in config files.');
        $this->info('Otherwise you can NOT cache your config files using "config:cache"');
        $this->info('https://laravel.com/docs/5.5/configuration#configuration-caching');

        return app(ErrorPrinter::class)->hasErrors() ? 1 : 0;
    }

    /**
     * @param  \Generator<int, string>|string[]  $paths
     * @return void
     */
    private function checkPaths($paths, $params)
    {
        foreach ($paths as $filePath) {
            EnvCallsCheck::check(PhpFileDescriptor::make($filePath), [$params]);
        }
    }

    private function checkPsr4Classes(PathFilterDTO $pathDTO, $params)
    {
        $configs = Paths::getAbsFilePaths(LaravelPaths::configDirs(), PathFilterDTO::make());

        $configs = trim(implode(',', array_keys(iterator_to_array($configs))), ',');
        if ($pathDTO->excludeFolder) {
            $pathDTO->excludeFolder = $pathDTO->excludeFolder.','.$configs;
        } else {
            $pathDTO->excludeFolder = $configs;
        }

        return ForPsr4LoadedClasses::check([EnvCallsCheck::class], [$params], $pathDTO);
    }
}
