<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\GetClassProperties;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\CheckBladeFiles;
use Imanghafoori\LaravelMicroscope\CheckClasses;
use Imanghafoori\LaravelMicroscope\Checks\CheckClassReferences;
use Imanghafoori\LaravelMicroscope\Checks\CheckRouteCalls;
use Imanghafoori\LaravelMicroscope\Analyzers\FunctionCall;
use Imanghafoori\LaravelMicroscope\Checks\CheckViewFilesExistence;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckViews extends Command
{
    use LogsErrors;

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
     * @param  ErrorPrinter  $errorPrinter
     *
     * @return mixed
     */
    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking views...');

        $errorPrinter->printer = $this->output;

        $psr4 = ComposerJson::readKey('autoload.psr-4');

        foreach ($psr4 as $namespace => $path) {
            $this->checkAllClasses(FilePath::getAllPhpFiles($path));
        }

        $checks = [
            [CheckViewFilesExistence::class, 'check'],
            [CheckClassReferences::class, 'check'],
            [CheckRouteCalls::class, 'check'],
        ];

        CheckBladeFiles::applyChecks($checks);

        $this->finishCommand($errorPrinter);
    }

    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $classes
     *
     * @return void
     */
    public function checkAllClasses($classes)
    {
        foreach ($classes as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();

            if (! CheckClasses::hasOpeningTag($absFilePath)) {
                continue;
            }

            [$namespace, $class] = GetClassProperties::fromFilePath($absFilePath);

            if ($class && $namespace) {
                $this->checkForViewMake($absFilePath);
            }
        }
    }

    private function checkForViewMake($absFilePath)
    {
        $tokens = token_get_all(file_get_contents($absFilePath));

        foreach($tokens as $i => $token) {
            $index = FunctionCall::isGlobalCall('view', $tokens, $i) || FunctionCall::isStaticCall('make', $tokens, $i, 'View');

            if (! $index) {
                continue;
            }

            $params = FunctionCall::readParameters($tokens, $i);

            $param1 = null;
            // it should be a hard-coded string which is not concatinated like this: 'hi'. $there
            $paramTokens = $params[0] ?? ['_', '_'];

            if(! FunctionCall::isSolidString($paramTokens)) {
                continue;
            }

            $p1 = trim($paramTokens[0][1], '\'\"');

            $p1 && ! View::exists($p1) && app(ErrorPrinter::class)->view($absFilePath, 'view does not exist', $paramTokens[0][2], $p1);
        }
    }
}
