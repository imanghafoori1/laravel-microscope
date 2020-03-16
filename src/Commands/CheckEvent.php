<?php

namespace Imanghafoori\LaravelSelfTest;

use Illuminate\Console\Command;

class CheckEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the validity of event listeners';

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
