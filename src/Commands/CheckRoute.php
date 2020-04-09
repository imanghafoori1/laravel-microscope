<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckRoute extends Command
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
        foreach ($routes as $route) {
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
                $this->errorIt($route);
                app(ErrorPrinter::class)->print('The controller can not be resolved: '.$ctrlClass);

                return;
            }

            if (! method_exists($ctrlObject, $method)) {
                $this->errorIt($route);
                app(ErrorPrinter::class)->print('The controller action does not exist: '.$ctrl);
            }
        }

        $this->finishCommand($errorPrinter);
    }

    public function errorIt($route)
    {
        $p = app(ErrorPrinter::class);
        if ($routeName = $route->getName()) {
            $p->print('Error on route name: '.$routeName);
        } else {
            $p->print('Error on route url: '.$route->uri());
        }
    }
}
