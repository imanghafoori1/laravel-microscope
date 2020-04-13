<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
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
        $this->info('Checking routes ...');

        $errorPrinter->printer = $this->output;

        $routes = app(Router::class)->getRoutes()->getRoutes();

        $bar = $this->output->createProgressBar(count($routes));

        $bar->start();

        foreach ($routes as $route) {
            $bar->advance();

            if (! is_string($ctrl = $route->getAction()['uses'])) {
                continue;
            }

            [
                $ctrlClass,
                $method,
            ] = Str::parseCallback($ctrl, '__invoke');

            try {
                $ctrlObject = app()->make($ctrlClass);
            } catch (BindingResolutionException $e) {
                $errorIt = $this->errorIt($route);
                $errorCtrlClass = 'The controller can not be resolved: ';
                $errorPrinter->route($ctrlClass, $errorIt, $errorCtrlClass);

                return;
            }

            if (! method_exists($ctrlObject, $method)) {
                $errorIt = $this->errorIt($route);
                $errorCtrl = 'The controller action does not exist: ';
                $errorPrinter->route($ctrl, $errorIt, $errorCtrl);
            }
        }

        $bar->finish();

        $this->finishCommand($errorPrinter);
    }

    public function errorIt($route)
    {
        if ($routeName = $route->getName()) {
            return 'Error on route name: '.$routeName;
        } else {
            return 'Error on route url: '.$route->uri();
        }
    }
}
