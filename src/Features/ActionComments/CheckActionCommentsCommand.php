<?php

namespace Imanghafoori\LaravelMicroscope\Features\ActionComments;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class CheckActionCommentsCommand extends BaseCommand
{
    protected $signature = 'check:action_comments
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $description = 'Adds route definition to the controller actions';

    public $checks = [ActionsComments::class];

    public $initialMsg = 'Commentify Route Actions...';

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        ActionsComments::$command = $this;
        ActionsComments::$controllers = self::findDefinedRouteActions();
        ActionsComments::$allRoutes = app('router')->getRoutes()->getRoutes();

        $iterator->formatPrintPsr4();
    }

    private static function findDefinedRouteActions()
    {
        $results = [];
        foreach (app('router')->getRoutes()->getRoutes() as $route) {
            $uses = $route->action['uses'] ?? null;
            if (is_string($uses) && Str::contains($uses, '@')) {
                [$class, $method] = Str::parseCallback($uses);
                $results[trim($class, '\\')] = $method;
            }
        }

        return $results;
    }
}
