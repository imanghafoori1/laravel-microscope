<?php

namespace Imanghafoori\LaravelSelfTest\Commands;

use Illuminate\Console\Command;

class CheckAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all checks with one command.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // $this->call('check:auth');
        $this->call('check:event');
        $this->call('check:gate');
        $this->call('check:import');
        $this->call('check:psr4');
        $this->call('check:route');
    }
}
