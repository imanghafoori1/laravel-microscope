<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorPrinter;

class CheckRoute extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:route';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the validity of route definitions';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
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
