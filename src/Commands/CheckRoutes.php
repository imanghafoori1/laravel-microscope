<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Routing\Router;
use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Psr4Classes;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Checks\CheckRouteCalls;
use Imanghafoori\LaravelMicroscope\Checks\RoutelessActions;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckRoutes extends Command
{
    use LogsErrors;

    protected $signature = 'check:routes';

    protected $description = 'Checks the validity of route definitions';

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Checking route definitions...');

        $errorPrinter->printer = $this->output;

        $routes = app(Router::class)->getRoutes()->getRoutes();
        $this->checkRouteDefinitions($errorPrinter, $routes);
        // checks calls like this: route('admin.user')
        // in the psr-4 loaded classes.
        $this->info('Searching for route-less controller actions...');
        Psr4Classes::check([RoutelessActions::class]);

        $this->info('Checking route names exists...');
        Psr4Classes::check([CheckRouteCalls::class]);
        BladeFiles::check([CheckRouteCalls::class]);
        $this->finishCommand($errorPrinter);
        $errorPrinter->printTime();

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private function getRouteId($route)
    {
        if ($routeName = $route->getName()) {
            $msg = 'name: '.$routeName;
        } else {
            $msg = 'url: '.$route->uri();
        }

        return 'Error on route '.$msg;
    }

    private function checkRouteDefinitions($errorPrinter, $routes)
    {
        foreach ($routes as $route) {
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
}
