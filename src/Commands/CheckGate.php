<?php

namespace Imanghafoori\LaravelSelfTest\Commands;

use Illuminate\Console\Command;

class CheckGate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:gate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the validity of gate definitions';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
    }
}
