<?php

namespace Imanghafoori\LaravelSelfTest\Commands;

use Imanghafoori\LaravelSelfTest\ErrorPrinter;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;

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

            if (! class_exists($ctrlClass)) {
                $this->errorIt($route, 'The controller does not exist: '.$ctrlClass);
            }

            if (! method_exists($ctrlClass, $method)) {
                $this->errorIt($route, 'The controller action does not exist: '.$ctrl);
            }
        }
    }

    public function errorIt($route, $msg)
    {
        $p = app(ErrorPrinter::class);
        if ($routeName = $route->getName()) {
            $p->print('Error on route name: '.$routeName);
        } else {
            $p->print('Error on route url: '.$route->uri());
        }
        $p->print($msg);
    }
}
