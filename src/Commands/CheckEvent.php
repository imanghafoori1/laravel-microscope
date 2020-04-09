<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorPrinter;

class CheckEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:events';

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
        $this->info('Checking events ...');

        app(ErrorPrinter::class)->printer = $this->output;

        $this->info('All your events are correct!');
    }
}
