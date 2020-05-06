<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\CheckBladeFiles;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Checks\CheckRouteCalls;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Symfony\Component\Console\Helper\ProgressBar;

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
        $this->checkClassesForRouteCalls();

        CheckBladeFiles::applyChecks([
            [CheckRouteCalls::class, 'check'],
        ]);

        $bar->finish();

        $this->finishCommand($errorPrinter);
    }

    public function getRouteId($route)
    {
        return ($routeName = $route->getName())
        ? "Error on route name: {$routeName}"
        : "Error on route url: {$route->uri()}";
    }

    protected function checkClassesForRouteCalls()
    {
        $psr4 = ComposerJson::readKey('autoload.psr-4');

        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            foreach ($files as $classFilePath) {
                $absFilePath = $classFilePath->getRealPath();
                CheckRouteCalls::check(token_get_all(file_get_contents($absFilePath)), $absFilePath);
            }
        }
    }

    private function checkRouteDefinitions(ErrorPrinter $errorPrinter, array $routes, ProgressBar $bar)
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
                $errorPrinter->route($ctrlClass, $this->getRouteId($route), 'The controller can not be resolved: ');
                continue;
            }

            method_exists($ctrlObj, $method) || $errorPrinter->route($ctrl, $this->getRouteId($route), 'Absent Method: ');
        }
    }
}
