<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEvents;

use Illuminate\Console\Command;

class CheckEventsCommand extends Command
{
    protected $signature = 'check:events';

    protected $description = 'This command is removed in this new version.';

    public $checks = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->getOutput()->writeln(' - This command is removed as useless, and does not do anything in the new version. ');

        return 0;
    }
}
