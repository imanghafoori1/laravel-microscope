<?php

namespace Imanghafoori\LaravelMicroscope\Features\ActionComments;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class CheckActionComments extends Command
{
    use LogsErrors;

    protected $signature = 'check:action_comments
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $description = 'Adds route definition to the controller actions';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $errorPrinter->printer = $this->output;

        $this->info('Commentify Route Actions...');

        ActionsComments::$command = $this;

        ActionsComments::$controllers = self::findDefinedRouteActions();

        $psr4Stats = ForPsr4LoadedClasses::check(
            [ActionsComments::class],
            [],
            PathFilterDTO::makeFromOption($this)
        );
        iterator_to_array($psr4Stats);

        return 0;
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
