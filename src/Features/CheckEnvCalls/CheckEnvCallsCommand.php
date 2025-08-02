<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEnvCalls;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;
use Imanghafoori\TokenAnalyzer\FunctionCall;
use Imanghafoori\TokenAnalyzer\TokenManager;

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

        $this->checkPaths(RoutePaths::get());

        $pathDTO = PathFilterDTO::makeFromOption($this);

        $paths = LaravelPaths::getMigrationsFiles($pathDTO);

        foreach ($paths as $path) {
            $this->checkPaths($path);
        }

        $this->checkPsr4Classes($pathDTO);

        event('microscope.finished.checks', [$this]);
        $this->info('&It is recommended use env() calls, only and only in config files.');
        $this->info('Otherwise you can NOT cache your config files using "config:cache"');
        $this->info('https://laravel.com/docs/5.5/configuration#configuration-caching');

        return app(ErrorPrinter::class)->hasErrors() ? 1 : 0;
    }

    /**
     * @param  string  $absPath
     * @return void
     */
    private function checkForEnv($absPath)
    {
        $tokens = token_get_all(file_get_contents($absPath));

        foreach ($tokens as $i => $token) {
            if ($index = FunctionCall::isGlobalCall('env', $tokens, $i)) {
                if (! $this->isLikelyConfigFile($absPath, $tokens)) {
                    EnvFound::warn($absPath, $tokens[$index][2], $tokens[$index][1]);
                }
            }
        }
    }

    /**
     * @param  \Generator<int, string>|string[]  $paths
     * @return void
     */
    private function checkPaths($paths)
    {
        foreach ($paths as $filePath) {
            $this->checkForEnv($filePath);
        }
    }

    private function checkPsr4Classes(PathFilterDTO $pathDTO)
    {
        $configs = Paths::getAbsFilePaths(LaravelPaths::configDirs(), PathFilterDTO::make());

        $configs = trim(implode(',', array_keys(iterator_to_array($configs))), ',');
        if ($pathDTO->excludeFolder) {
            $pathDTO->excludeFolder = $pathDTO->excludeFolder.','.$configs;
        } else {
            $pathDTO->excludeFolder = $configs;
        }

        $psr4Stats = ForPsr4LoadedClasses::check([EnvCallsCheck::class], [], $pathDTO);

        Psr4Report::printAutoload($psr4Stats, [], $this->getOutput());
        CachedFiles::writeCacheFiles();
    }

    private function isLikelyConfigFile($absPath, $tokens)
    {
        [$token] = TokenManager::getNextToken($tokens, 0);

        if ($token[0] === T_NAMESPACE) {
            return false;
        }

        if ($token[0] === T_RETURN && strpos(strtolower($absPath), 'config')) {
            return true;
        }

        return basename($absPath) === 'config.php';
    }
}
