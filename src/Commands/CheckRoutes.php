<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\Checks\ActionsComments;
use Imanghafoori\LaravelMicroscope\Checks\CheckRouteCalls;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
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
        app(Filesystem::class)->delete(app()->getCachedRoutesPath());

        $routes = app(Router::class)->getRoutes()->getRoutes();
        $this->checkRouteDefinitions($errorPrinter, $routes);
        // checks calls like this: route('admin.user')
        $this->getOutput()->writeln(
            ' - '.CheckRoutes::$checkedRoutesNum.
            ' Route:: definitions were checked. ('.
            CheckRoutes::$skippedRoutesNum.' skipped)'
        );
        $this->info('Checking route names exists...');
        ForPsr4LoadedClasses::check([CheckRouteCalls::class]);
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
            $msg = 'name: "'.$routeName.'"';
        } else {
            $msg = 'url: "'.$route->uri().'"';
        }

        return $msg;
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
                $msg2 = 'The controller can not be resolved: ('.$msg1.')';
                [$path, $line] = ActionsComments::getCallsiteInfo($route->methods()[0], $route);
                $errorPrinter->route($ctrlClass, $msg2, '', $path, $line);

                continue;
            }

            if (! method_exists($ctrlObj, $method)) {
                $msg2 = 'Absent method for route'.' '.$this->getRouteId($route);
                [$path, $line] = ActionsComments::getCallsiteInfo($route->methods()[0], $route);
                $errorPrinter->route($ctrl, $msg2, '', $path, $line);
            }
        }
    }
}
