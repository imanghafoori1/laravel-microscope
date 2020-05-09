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

                $fullNamespace = $this->getFullNamespace($classFilePath, $psr4Path, $psr4Namespace);

                if ($this->isLaravelController($fullNamespace)) {
                    $class = ClassMethods::read($tokens);

                    $methods = $this->getControllerActions($class['methods']);

                    foreach ($methods as $method) {
                        $classAtMethod = trim($fullNamespace, '\\').'@'.$method['name'][1];
                        if (! app('router')->getRoutes()->getByAction($classAtMethod)) {
                            $line = $method['name'][2];
                            $errorPrinter->routelessAction($absFilePath, $line, $classAtMethod);
                        }
                    }
                }

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

    private function getControllerActions($methods)
    {
        $orphanMethods = [];
        foreach ($methods as $method) {
            // we exclude non-public methods
            if ($method['visibility'][0] !== T_PUBLIC) {
                continue;
            }

            $methodName = $method['name'][1];
            // we exclude __construct
            if ($methodName == '__construct') {
                continue;
            }

            ($methodName == '__invoke') && ($methodName = '');

            $methodName && ($methodName = '@'.$methodName);

            $orphanMethods[] = $method;
        }

        return $orphanMethods;
    }

    protected function getNamespacedClassName($classFilePath, $psr4Path, $psr4Namespace)
    {
        $absFilePath = $classFilePath->getRealPath();
        $className = $classFilePath->getFilename();
        $relativePath = str_replace(base_path(), '', $absFilePath);
        $namespace = NamespaceCorrector::calculateCorrectNamespace($relativePath, $psr4Path, $psr4Namespace);

        return $namespace.'\\'.$className;
    }

    protected function isLaravelController($fullNamespace)
    {
        try {
            return is_subclass_of($fullNamespace, Controller::class);
        } catch (\Throwable $r) {
            // it means the file does not contain a class or interface.
            return false;
        }
    }

    /**
     * @param $classFilePath
     * @param $psr4Path
     * @param $psr4Namespace
     *
     * @return string
     */
    protected function getFullNamespace($classFilePath, $psr4Path, $psr4Namespace)
    {
        $fullNamespace = $this->getNamespacedClassName($classFilePath, $psr4Path, $psr4Namespace);
        $fullNamespace = trim($fullNamespace, '.php');

        return $fullNamespace;
    }
}
