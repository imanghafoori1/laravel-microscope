<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEvents;

use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class CheckEventsCommand extends BaseCommand
{
    protected $signature = 'check:events';

    protected $description = 'Checks the validity of event listeners';

    public $initialMsg = 'Checking events...';

    public $checks = [];

    public $customMsg = 'All the events are ok.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handleCommand()
    {
        $this->getOutput()->writeln(' - '.SpyDispatcher::$listeningNum.' listenings were checked.');

        return $this->exitCode();
    }
}
