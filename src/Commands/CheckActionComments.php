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

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private static function findDefinedRouteActions()
    {
        $results = [];
        foreach (app('router')->getRoutes()->getRoutes() as $route) {
            $uses = $route->action['uses'] ?? null;
            if (is_string($uses) && Str::contains($uses, '@')) {
                [$class, $method] = Str::parseCallback($uses);
                $results[$class] = $method;
            }
        }

        return $results;
    }
}
