<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\Console;

class Options
{
    public $noRefFix;

    public $noFix;

    public $force;

    public $forceRefFix;

    public function __construct($noRefFix, $noFix, $force, $forceRefFix)
    {
        $this->noRefFix = $noRefFix;
        $this->noFix = $noFix;
        $this->force = $force;
        $this->forceRefFix = $forceRefFix;
    }
}
