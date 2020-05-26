<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Console\Command;

class PrettyPrintRoutes extends Command
{
    protected $signature = 'pp:routes';

    protected $description = 'Pretty print routes';

    public function handle()
    {
        $values = config('microscope.pp.routes', []);
        foreach ($values as $val) {
            $val = trim($val);
            if (Str::containsAll($val, ['@', '\\'])) {
                $route = app('routes')->getByAction($val);
            } else {
                $route = app('routes')->getByName($val);
            }

            if ($route) {
                $this->printIt($route);
            } else {
                $this->info('Route name not found.');
            }
        }
    }

    /**
     * @param  $r \Illuminate\Routing\Route
     */
    private function printIt($r)
    {
        try {
            $middlewares = $r->gatherMiddleware();
            $middlewares && $middlewares = "'".implode("', '", $r->gatherMiddleware())."'";
            $this->getOutput()->writeln('---------------------------------------------------');
            $this->info(' name:             '.($r->getName() ? ($r->getName()): ''));
            $this->info(' uri:              '.implode(', ', $r->methods())."   '/".$r->uri()."'  ");
            $this->info(' middlewares:      '.$middlewares);
            $this->info(' action:           '.$r->getActionName());

            $methods = $r->methods();
            ($methods == ['GET', 'HEAD']) && $methods = ['GET'];

            $action = $this->getAction($r->getActionName());

            if (count($methods)  == 1) {
                $this->getOutput()->writeln(PHP_EOL.$this->getMovableRoute($r, $methods, $action, $middlewares));
            }
        } catch (Exception $e) {
            $this->info('The route has some problem.');
            $this->info($e->getMessage());
            $this->info($e->getFile());

            return;
        }
    }

    /**
     * @param $r
     * @param  array  $methods
     * @param  string  $action
     * @param $middlewares
     *
     * @return string
     */
    private function getMovableRoute($r, array $methods, string $action, $middlewares): string
    {
        return 'Route::'.strtolower($methods[0])."('/".$r->uri()."', ".$action.")".PHP_EOL.($middlewares ? '->middleware(['.$middlewares."])" : '').($r->getName() ? ("->name('".$r->getName()."')") : '').';';
    }

    /**
     * @param $action
     *
     * @return array|string|void
     */
    private function getAction($action)
    {
        if (! Str::contains($action, ['@'])) {
            return "\\".$action."::class";
        }

        $action = explode('@', $action);

        return "["."\\".$action[0]."::class".", '".$action[1]."']";
    }
}
