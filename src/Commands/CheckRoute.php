<?php

namespace Imanghafoori\LaravelSelfTest\Commands;

use Illuminate\Support\Str;
use Illuminate\Routing\Router;
use Illuminate\Console\Command;
use Imanghafoori\LaravelSelfTest\ErrorPrinter;
use Imanghafoori\LaravelSelfTest\View\ViewParser;
use Imanghafoori\LaravelSelfTest\ControllerParser;
use Illuminate\Contracts\Container\BindingResolutionException;

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
                return ;
            }

            if (! method_exists($ctrlObject, $method)) {
                $this->errorIt($route);
                app(ErrorPrinter::class)->print('The controller action does not exist: '.$ctrl);
            }

            $this->checkViews($ctrlObject, $method);
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

    /**
     * @param $method
     * @param $ctrl
     */
    protected function checkViews($ctrl, $method)
    {
        $controllerAction = (new ControllerParser())->parse($ctrl, $method);
        $vParser = new ViewParser($controllerAction);
        $views = $vParser->parse()->getChildren();
        $this->checkView($ctrl, $method, $views);
    }

    protected function checkView($ctrl, $method, array $views): void
    {
        foreach ($views as $view => $_) {
            if ($_['children']) {
                $this->checkView($ctrl, $method, $_['children']);
            }

            if (! $_['children']) {
                dump($_['file'].' line number:'.$_['lineNumber'].  '  =>  '.trim($_['line']). '  file does not exist:  '.$_['name'].'.blade.php');
            }
        }
    }
}
