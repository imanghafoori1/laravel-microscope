<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Checks\ActionsComments;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckActionComments extends Command
{
    use LogsErrors;

    protected $signature = 'check:action_comments';

    protected $description = 'Adds route definition to the controller actions';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $errorPrinter->printer = $this->output;

        $this->info('Commentify Route Actions...');

        ActionsComments::$command = $this;

        ActionsComments::$controllers = self::findDefinedRouteActions();
        ForPsr4LoadedClasses::check([ActionsComments::class]);

        //$this->finishCommand($errorPrinter);

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private static function findDefinedRouteActions()
    {
        $results = [];
        foreach (app('router')->getRoutes()->getRoutes() as $route) {
            if (is_string($route->action['uses'] ?? null)) {
                $r = Str::parseCallback($route->action['uses']);
                $results[$r[0]] = $r[1];
            }
        }

        return $results;
    }
}
