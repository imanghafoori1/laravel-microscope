<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Routing\Router;
use Illuminate\Console\Command;
use Illuminate\Routing\Controller;
use Imanghafoori\LaravelMicroscope\CheckBladeFiles;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Checks\CheckRouteCalls;
use Imanghafoori\LaravelMicroscope\Analyzers\ClassMethods;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;

class CheckRoutes extends Command
{
    use LogsErrors;

    protected $signature = 'check:routes';

    protected $description = 'Checks the validity of route definitions';

    /**
     * Execute the console command.
     *
     * @param  ErrorPrinter  $errorPrinter
     *
     * @return mixed
     */
    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking routes...');

        $errorPrinter->printer = $this->output;

        $routes = app(Router::class)->getRoutes()->getRoutes();

        $bar = $this->output->createProgressBar(count($routes));

        $bar->start();

        $this->checkRouteDefinitions($errorPrinter, $routes, $bar);

        // checks calls like this: route('admin.user')
        // in the psr-4 loaded classes.
        $this->checkClassesForRouteCalls($errorPrinter);

        CheckBladeFiles::applyChecks([
            [CheckRouteCalls::class, 'check'],
        ]);

        $bar->finish();

        $this->finishCommand($errorPrinter);
    }

    public function getRouteId($route)
    {
        if ($routeName = $route->getName()) {
            return 'Error on route name: '.$routeName;
        } else {
            return 'Error on route url: '.$route->uri();
        }
    }

    protected function checkClassesForRouteCalls($errorPrinter)
    {
        $psr4 = ComposerJson::readKey('autoload.psr-4');

        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            foreach ($files as $classFilePath) {
                $absFilePath = $classFilePath->getRealPath();
                $tokens = token_get_all(file_get_contents($absFilePath));

                $class = ClassMethods::read($tokens);
                $this->checkActionHasRoute($errorPrinter, $class, $absFilePath, $psr4Path, $psr4Namespace);

                CheckRouteCalls::check($tokens, $absFilePath);
            }
        }
    }

    private function checkRouteDefinitions($errorPrinter, $routes, $bar)
    {
        foreach ($routes as $route) {
            $bar->advance();

            if (! is_string($ctrl = $route->getAction()['uses'])) {
                continue;
            }

            [$ctrlClass, $method] = Str::parseCallback($ctrl, '__invoke');

            try {
                $ctrlObj = app()->make($ctrlClass);
            } catch (Exception $e) {
                $msg1 = $this->getRouteId($route);
                $msg2 = 'The controller can not be resolved: ';
                $errorPrinter->route($ctrlClass, $msg1, $msg2);

                continue;
            }

            if (! method_exists($ctrlObj, $method)) {
                $msg1 = $this->getRouteId($route);
                $msg2 = 'Absent Method: ';
                $errorPrinter->route($ctrl, $msg1, $msg2);
            }
        }
    }

    protected function checkActionHasRoute($errorPrinter, $class, $absFilePath, $psr4Path, $psr4Namespace)
    {
        if (! ($class['name'])) {
            return;
        }

        $relativePath = str_replace(base_path(), '', $absFilePath);
        $namespace = NamespaceCorrector::calculateCorrectNamespace($relativePath, $psr4Path, $psr4Namespace);

        $isController = is_subclass_of($namespace.'\\'.$class['name'][1], Controller::class);
        foreach ($class['methods'] as $method) {
            // we exclude __construct and non-public methods
            if ($method['visibility'][0] !== T_PUBLIC || $method['name'][1] == '__construct') {
                continue;
            }

            $methodName = $method['name'][1] == '__invoke' ? '' : '@'.$method['name'][1];
            try {
                $isController && app('url')->action($namespace.'\\'.$class['name'][1].$methodName);
            } catch (\Exception $e) {
                $errorPrinter->routelessAction($absFilePath, $method['name'][2], $class['name'][1].$methodName);
            }
        }
    }
}
