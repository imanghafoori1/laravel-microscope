<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\Checks\ActionsComments;
use Imanghafoori\LaravelMicroscope\Checks\CheckRouteCalls;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Psr4Classes;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckRoutes extends Command
{
    use LogsErrors;

    public static $checkedRoutesNum = 0;

    public static $skippedRoutesNum = 0;

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
        $this->getOutput()->writeln(
            ' - '.CheckRoutes::$checkedRoutesNum.
            ' Route:: definitions were checked. ('.
            CheckRoutes::$skippedRoutesNum.' skipped)'
        );
        $this->info('Checking route names exists...');
        Psr4Classes::check([CheckRouteCalls::class]);
        BladeFiles::check([CheckRouteCalls::class]);

        $this->getOutput()->writeln(' - '.CheckRouteCalls::$checkedRouteCallsNum.
            ' route(...) calls were checked. ('
            .CheckRouteCalls::$skippedRouteCallsNum.' skipped)');

        event('microscope.finished.checks', [$this]);

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
                self::$skippedRoutesNum++;
                continue;
            }

            self::$checkedRoutesNum++;
            [$ctrlClass, $method] = Str::parseCallback($ctrl, '__invoke');

            try {
                $ctrlObj = app()->make($ctrlClass);
            } catch (Exception $e) {
                $msg1 = $this->getRouteId($route);
                $msg2 = 'The controller can not be resolved: ';
                [$path, $line] = ActionsComments::getCallsiteInfo($route->methods()[0], $route);
                $errorPrinter->route($ctrlClass, $msg1, $msg2, $path, $line);

                continue;
            }

            if (! method_exists($ctrlObj, $method)) {
                $msg1 = $this->getRouteId($route);
                $msg2 = 'Absent Method: ';
                [$path, $line] = ActionsComments::getCallsiteInfo($route->methods()[0], $route);
                $errorPrinter->route($ctrl, $msg1, $msg2, $path, $line);
            }
        }
    }
}
