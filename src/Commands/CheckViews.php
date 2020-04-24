<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;
use Imanghafoori\LaravelMicroscope\ErrorTypes\BladeFile;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\CheckBladeFiles;
use Imanghafoori\LaravelMicroscope\Checks\CheckClassReferences;
use Imanghafoori\LaravelMicroscope\Checks\CheckRouteCalls;
use Imanghafoori\LaravelMicroscope\Analyzers\FunctionCall;
use Imanghafoori\LaravelMicroscope\Checks\CheckViewFilesExistence;

class CheckViews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:views {--d|detailed : Show files being checked}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the validity of blade files';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Checking views...');

        $psr4 = ComposerJson::readKey('autoload.psr-4');

        foreach (RoutePaths::get() as $filePath) {
            $this->checkForViewMake($filePath, [
                'View' => ['make', 0],
                'Route' => ['view', 1],
            ]);
        }

        foreach ($psr4 as $_namespace => $dirPath) {
            foreach (FilePath::getAllPhpFiles($dirPath) as $filePath) {
                $this->checkForViewMake($filePath->getRealPath(), ['View' => ['make', 0]]);
            }
        }

        $checks = [
            [CheckViewFilesExistence::class, 'check'],
            [CheckClassReferences::class, 'check'],
            [CheckRouteCalls::class, 'check'],
        ];

        CheckBladeFiles::applyChecks($checks);

        event('microscope.finished.checks', [$this]);
    }

    private function checkForViewMake($absPath, $staticCalls)
    {
        $tokens = token_get_all(file_get_contents($absPath));

        foreach($tokens as $i => $token) {
            if (FunctionCall::isGlobalCall('view', $tokens, $i)) {
                $this->checkViewParams($absPath, $tokens, $i, 0);
                continue;
            }

            foreach ($staticCalls as $class => $method) {
                if (FunctionCall::isStaticCall($method[0], $tokens, $i, $class)) {
                    $this->checkViewParams($absPath, $tokens, $i, $method[1]);
                    continue;
                }
            }
        }
    }

    private function checkViewParams($absPath, &$tokens, $i, $index)
    {
        $params = FunctionCall::readParameters($tokens, $i);

        $param1 = null;
        // it should be a hard-coded string which is not concatinated like this: 'hi'. $there
        $paramToken = $params[$index] ?? ['_', '_', '_'];

        if (! FunctionCall::isSolidString($paramToken)) {
            return;
        }

        $viewName = trim($paramToken[0][1], '\'\"');

        if ($viewName && ! View::exists($viewName)) {
            BladeFile::isMissing($absPath, $paramToken[0][2], $viewName);
        }
    }
}
