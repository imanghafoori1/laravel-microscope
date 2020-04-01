<?php

namespace Imanghafoori\LaravelSelfTest\Commands;

use ReflectionException;
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
        $errorPrinter = app(ErrorPrinter::class);
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
                $errorPrinter->print('The controller can not be resolved: '.$ctrlClass);
                return ;
            }

            if (! method_exists($ctrlObject, $method)) {
                $this->errorIt($route);
                $errorPrinter->print('The controller action does not exist: '.$ctrl);
            }

            $this->checkViews($ctrlObject, $method);
        }
    }

    public function errorIt($route)
    {
        $errorPrinter = app(ErrorPrinter::class);
        if ($routeName = $route->getName()) {
            $errorPrinter->print('Error on route name: '.$routeName);
        } else {
            $errorPrinter->print('Error on route url: '.$route->uri());
        }
    }

    /**
     * @param $method
     * @param $ctrl
     */
    protected function checkViews($ctrl, $method)
    {
        $controllerMethod = (new ControllerParser())->parse($ctrl, $method);
        $params = $controllerMethod->getParameters();
        foreach ($params as $param) {
            try {
                $param->getClass();
            } catch (ReflectionException $e) {
                $errorPrinter = app(ErrorPrinter::class);
                $errorPrinter->print(
                    'The type hint in the "'. get_class($ctrl).'@'.$method. '" is wrong.'
                );
            }
        }

        $vParser = new ViewParser($controllerMethod);
        $views = $vParser->parse()->getChildren();

        $this->checkView($ctrl, $method, $views);
    }

    protected function checkView($ctrl, $method, array $views)
    {
        foreach ($views as $view => $_) {
            if ($_['children']) {
                $this->checkView($ctrl, $method, $_['children']);
            }

            if (! $_['children']) {
                $errorPrinter = app(ErrorPrinter::class);
                $errorPrinter->print(
                    $_['file'].', line number:'.$_['lineNumber']
                    .'  => '.($_['line'])
                    .'"'.$_['name'].'.blade.php" does not exist'
                );
           }
        }
    }
}
