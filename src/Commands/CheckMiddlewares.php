<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;

class CheckMiddlewares extends Command
{
    protected $signature = 'check:middlewares';

    protected $description = 'Checks the validity of blade files';

    public function handle()
    {
        $values = config('microscope.check.middleware');
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
            $this->getOutput()->writeln('---------------------------------------------------');
            $this->info('name:             '.($r->getName() ? ($r->getName()): ''));
            $this->info('uri:              '.implode(', ', $r->methods()).'   \'/'.$r->uri().'\'  ');
            $this->info('middlewares:      \''.implode('\', \'', $r->gatherMiddleware()).'\'');
            $this->info('action:           '.$r->getActionName());
        } catch (\Exception $e) {
            $this->info('The route has some problem.');
            $this->info($e->getMessage());
            $this->info($e->getFile());

            return;
        }
    }
}
