<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckGates;

use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class CheckGates extends BaseCommand
{
    protected $signature = 'check:gates';

    protected $description = 'Checks the validity of gate definitions';

    public $initialMsg = 'Checking gates...';

    public $checks = [];

    public $customMsg = 'Gates are ok';

    public function handleCommand()
    {
        $this->getOutput()->writeln(' - '.SpyGate::$definedGatesNum.' gate definitions were checked.');

        return $this->exitCode();
    }
}
