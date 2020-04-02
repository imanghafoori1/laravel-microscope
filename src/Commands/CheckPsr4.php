<?php

namespace Imanghafoori\LaravelSelfTest\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\LaravelSelfTest\ErrorPrinter;
use Imanghafoori\LaravelSelfTest\CheckClasses;

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
        $composer = json_decode(file_get_contents(app()->basePath('composer.json')), true);

        foreach ((array)data_get($composer, 'autoload.psr-4') as $namespace => $path) {
            CheckClasses::within($path, $namespace);
        }

        $this->checkConfig();
    }

    protected function checkConfig()
    {
        $user = config('auth.providers.users.model');
        if (! $user || ! class_exists($user) || ! is_subclass_of($user, Model::class)) {
            resolve(ErrorPrinter::class)->print('The user model in the "config/auth.php" is not a valid class.');
        }
    }
}
