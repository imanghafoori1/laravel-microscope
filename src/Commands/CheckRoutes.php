<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Checks\CheckRouteCalls;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckRoutes extends Command
{
    use LogsErrors;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:routes';

    /**
     * The console command description.
     *
     * @var string
     */
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
        $this->checkClassesRouteCalls();

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

    protected function checkClassesRouteCalls()
    {
        $psr4 = ComposerJson::readKey('autoload.psr-4');

        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            foreach ($files as $classFilePath) {
                $absFilePath = $classFilePath->getRealPath();
                $tokens = token_get_all(file_get_contents($absFilePath));
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
                $msg2 = 'The controller action does not exist: ';
                $errorPrinter->route($ctrl, $msg1, $msg2);
            }
        }
    }
}
