<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Checks\CheckRouteCalls;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\ActionComments\ActionsComments;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Iterators\BladeFiles;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckRoutes extends Command
{
    use LogsErrors;

    public static $checkedRoutesNum = 0;

    public static $skippedRoutesNum = 0;

    protected $signature = 'check:routes
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $description = 'Checks the validity of route definitions';

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Checking route definitions...');

        $errorPrinter->printer = $this->output;
        app(Filesystem::class)->delete(app()->getCachedRoutesPath());

        $routes = app(Router::class)->getRoutes()->getRoutes();

        $this->checkRouteDefinitions($routes);
        // checks calls like this: route('admin.user')
        $this->getOutput()->writeln(
            $this->getRouteDefinitionStatistics()
        );

        $this->info('Checking route names exists...');
        $pathDTO = PathFilterDTO::makeFromOption($this);
        ForPsr4LoadedClasses::checkNow([CheckRouteCalls::class], [], $pathDTO);
        iterator_to_array(BladeFiles::check([CheckRouteCalls::class], [], $pathDTO));

        $this->getOutput()->writeln(
            $this->getStatisticsMsg()
        );

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

    private function checkRouteDefinitions($routes)
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
                // Starts with: "SQLSTATE"
                if (substr($e->getMessage(), 0, strlen('SQLSTATE')) === 'SQLSTATE') {
                    dump($ctrlClass.'  -  '.$e->getMessage());
                    continue;
                }

                $msg1 = $this->getRouteId($route);
                $msg2 = 'The controller can not be resolved: ('.$msg1.')';
                [$path, $line] = ActionsComments::getCallsiteInfo($route->methods()[0], $route);
                self::route($ctrlClass, $msg2, '', $path, $line);

                continue;
            }

            if (! method_exists($ctrlObj, $method)) {
                $msg2 = 'Absent method for route'.' '.$this->getRouteId($route);
                [$path, $line] = ActionsComments::getCallsiteInfo($route->methods()[0], $route);
                self::route($ctrl, $msg2, '', $path, $line);
            }
        }
    }

    public static function route($path, $errorIt, $errorTxt, $absPath = null, $lineNumber = 0)
    {
        $p = ErrorPrinter::singleton();
        $p->simplePendError($path, $absPath, $lineNumber, 'route', $errorIt, $errorTxt);
    }

    private function getStatisticsMsg()
    {
        return ' - '.CheckRouteCalls::$checkedRouteCallsNum.' route(...) calls were checked. ('.CheckRouteCalls::$skippedRouteCallsNum.' skipped)';
    }

    private function getRouteDefinitionStatistics()
    {
        return ' - '.CheckRoutes::$checkedRoutesNum.' Route:: definitions were checked. ('.CheckRoutes::$skippedRoutesNum.' skipped)';
    }
}
