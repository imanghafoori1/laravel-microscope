<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Console\Command;

class PrettyPrintListeners extends Command
{
    protected $signature = 'pp:routes';

    protected $description = 'Pretty print routes';

    public function handle()
    {
        $calls = config('microscope.write.routes', []);

        foreach ($calls as $call) {
            foreach ($call['args'] as $val) {
                is_array($val) && ($val = implode('@', $val));
                $val = trim($val ?? '');

                $route = $this->deduceRoute($val);

                if ($route) {
                    ($call["function"] == "microscope_write_route") && $this->writeIt($route, $call['file']);
                    ($call["function"] == "microscope_pretty_print_route") && $this->printIt($route);
                } else {
                    $this->info('Route name not found.');
                }
            }
        }
    }

    private function writeIt($r, $filename)
    {
        try {
            $middlewares = $this->getMiddlewares($r);

            $methods = $r->methods();
            ($methods == ['GET', 'HEAD']) && $methods = ['GET'];

            $action = $this->getAction($r->getActionName());

            if (count($methods)  == 1) {

                $definition = PHP_EOL.$this->getMovableRoute($r, $methods, $action, $middlewares);

                file_put_contents($filename, $definition, FILE_APPEND);
            }
        } catch (Exception $e) {
            $this->handleRouteProblem($e);

            return;
        }
    }

    private function deduceRoute($value)
    {
        if (Str::containsAll($value, ['@', '\\'])) {
            $route = app('routes')->getByAction($value);
        } else {
            $route = app('routes')->getByName($value);
        }

        return $route;
    }

    private function printIt($r)
    {
        try {
            $middlewares = $this->getMiddlewares($r);
            $this->prettyPrintInConsole($r, $middlewares);
        } catch (Exception $e) {
            $this->handleRouteProblem($e);

            return;
        }
    }

    private function getMovableRoute($r, $methods, $action, $middlewares)
    {
        $nameSection = ($r->getName() ? ("->name('".$r->getName()."')") : '');
        $middlewareSection = ($middlewares ? '->middleware(['.$middlewares."])" : '');
        $uriAction = "('/".$r->uri()."', ".$action.")";

        $method = strtolower($methods[0]);

        return 'Route::'.$method.$uriAction.PHP_EOL.$middlewareSection.$nameSection.';';
    }

    private function getAction($action)
    {
        if (! Str::contains($action, ['@'])) {
            return "\\".$action."::class";
        }

        $action = explode('@', $action);

        return "["."\\".$action[0]."::class".", '".$action[1]."']";
    }

    private function getMiddlewares($r)
    {
        $middlewares = $r->gatherMiddleware();
        $middlewares && $middlewares = "'".implode("', '", $r->gatherMiddleware())."'";

        return $middlewares;
    }

    private function handleRouteProblem($e)
    {
        $this->info('The route has some problem.');
        $this->info($e->getMessage());
        $this->info($e->getFile());
    }

    private function prettyPrintInConsole($r, $middlewares)
    {
        $this->getOutput()->writeln('---------------------------------------------------');
        $this->info(' name:             '.($r->getName() ? ($r->getName()) : ''));
        $this->info(' uri:              '.implode(', ', $r->methods())."   '/".$r->uri()."'  ");
        $this->info(' middlewares:      '.$middlewares);
        $this->info(' action:           '.$r->getActionName());
    }
}
