<?php

namespace Imanghafoori\LaravelSelfTest\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\LaravelSelfTest\ErrorPrinter;

class CheckAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:auth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the validity of auth';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $user = config('auth.providers.users.model');
        if (! class_exists($user) or ! is_subclass_of($user, Model::class)) {
            resolve(ErrorPrinter::class)->print('The user model in the "config/auth.php" is not a valid class.');
        }
    }
}
