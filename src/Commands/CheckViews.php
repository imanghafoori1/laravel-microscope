<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\GetClassProperties;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\CheckBladeFiles;
use Imanghafoori\LaravelMicroscope\CheckClasses;
use Imanghafoori\LaravelMicroscope\Checks\CheckClassReferences;
use Imanghafoori\LaravelMicroscope\Checks\CheckRouteCalls;
use Imanghafoori\LaravelMicroscope\Analyzers\GlobalFunctionCall;
use Imanghafoori\LaravelMicroscope\Checks\CheckViewFilesExistence;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\View\ViewParser;
use ReflectionClass;

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
                $this->checkViewsMake($namespace.'\\'.$class);
            }
        }
    }

    /**
     * @param $class
     */
    protected function checkViewsMake($class)
    {
        try {
            $methods = self::get_class_methods(new ReflectionClass($class));
        } catch (\ReflectionException $e) {
            $methods = [];
        }

        foreach ($methods as $method) {
            $vParser = new ViewParser($method);
            $views = $vParser->retrieveViewsFromMethod();

            if ($this->option('detailed')) {
                $this->line("Checking {$method->name} on {$method->class}");
            }

            self::checkView($views);
        }
    }

    protected static function checkView($views)
    {
        foreach ($views as $view => $_) {
            // in order to exclude dynamic parameters like: view($myView)
            if (! Str::contains($_['name'], ['$', '->', ' ']) && ! View::exists($_['name'])) {
                app(ErrorPrinter::class)->view($_['file'], $_['line'], $_['lineNumber'], $_['name']);
            }
        }
    }

    public static function get_class_methods($classReflection)
    {
        $className = $classReflection->getName();
        $methods = $classReflection->getMethods();

        $functions = [];
        foreach ($methods as $f) {
            ($f->class === $className) && $functions[] = $f;
        }

        return $functions;
    }

    private function checkForViewMake($absFilePath)
    {
        $tokens = token_get_all(file_get_contents($absFilePath));

        foreach($tokens as $i => $token) {
            $token = GlobalFunctionCall::isGlobalFunctionCall('view', $tokens, $i);

            if (! $token) {
                continue;
            }

            $params = GlobalFunctionCall::readParameters($tokens, $i);

            $param1 = null;
            // it should be a hard-coded string which is not concatinated like this: 'hi'. $there
            $paramTokens = $params[0] ?? ['_', '_'];

            if(! GlobalFunctionCall::isSolidString($paramTokens)) {
                continue;
            }

            $p1 = trim($paramTokens[0][1], '\'\"');

            $p1 && ! View::exists($p1) && app(ErrorPrinter::class)->view($absFilePath, 'view does not exist', $paramTokens[0][2], $p1);
        }
    }
}
