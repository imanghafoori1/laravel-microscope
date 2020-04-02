<?php

namespace Imanghafoori\LaravelSelfTest\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelSelfTest\CheckClasses;
use Imanghafoori\LaravelSelfTest\CheckViewRoute;

class CheckImports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the validity of use statements';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $composer = json_decode(file_get_contents(app()->basePath('composer.json')), true);
        $psr4 = (array) data_get($composer, 'autoload.psr-4');

        foreach ($psr4 as $namespace => $path) {
            CheckClasses::import($namespace, $path);
        }

        (new CheckViewRoute)->check();
    }
}
