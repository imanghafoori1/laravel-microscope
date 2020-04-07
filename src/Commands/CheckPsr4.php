<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\CheckClasses;
use Imanghafoori\LaravelMicroscope\ErrorPrinter;

class CheckPsr4 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:psr4';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the validity of namespaces';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        app(ErrorPrinter::class)->printer = $this->output;
        $composer = json_decode(file_get_contents(app()->basePath('composer.json')), true);
        $psr4 = (array) data_get($composer, 'autoload.psr-4');

        foreach ($psr4 as $namespace => $path) {
            CheckClasses::within($namespace, $path);
        }
    }
}
