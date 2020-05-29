<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\ErrorTypes\ddFound;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Analyzers\FunctionCall;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\LaravelPaths\MigrationPaths;

class CheckDD extends Command
{
    protected $signature = 'check:dd {--d|detailed : Show files being checked}';

    protected $description = 'Checks the debug functions';

    public function handle()
    {
        event('microscope.start.command');
        $this->info('Checking dd...');

        $this->checkPaths(RoutePaths::get());
        $this->checkPaths(Paths::getAbsFilePaths(MigrationPaths::get()));
        $this->checkPaths(Paths::getAbsFilePaths(app()->databasePath()));
        $this->checkPsr4Classes();

        event('microscope.finished.checks', [$this]);

        return app(ErrorPrinter::class)->hasErrors() ? 1 : 0;
    }

    private function checkForDD($absPath)
    {
        $tokens = token_get_all(file_get_contents($absPath));

        foreach($tokens as $i => $token) {
            if (
                ($index = FunctionCall::isGlobalCall('dd', $tokens, $i)) ||
                ($index = FunctionCall::isGlobalCall('microscope_pretty_print_route', $tokens, $i)) ||
                ($index = FunctionCall::isGlobalCall('microscope_dd_listeners', $tokens, $i)) ||
                ($index = FunctionCall::isGlobalCall('microscope_write_route', $tokens, $i)) ||
                ($index = FunctionCall::isGlobalCall('dump', $tokens, $i)) ||
                ($index = FunctionCall::isGlobalCall('ddd', $tokens, $i))
            ) {
                ddFound::isMissing($absPath, $tokens[$index][2], $tokens[$index][1]);
            }
        }
    }

    private function checkPaths($paths)
    {
        foreach ($paths as $filePath) {
            $this->checkForDD($filePath);
        }
    }

    private function checkPsr4Classes()
    {
        $psr4 = ComposerJson::readAutoload();

        foreach ($psr4 as $_namespace => $dirPath) {
            foreach (FilePath::getAllPhpFiles($dirPath) as $filePath) {
                $this->checkForDD($filePath->getRealPath());
            }
        }
    }
}
