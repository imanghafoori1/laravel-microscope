<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDD;

use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class CheckDDCommand extends BaseCommand
{
    protected $signature = 'check:dd
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
';

    protected $description = 'Checks the debug functions.';

    public $checks = [CheckDD::class];

    public $initialMsg = 'Checking for debug functions...';

    public $customMsg = '';

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        $iterator->printAll([
            $iterator->forComposerLoadedFiles(),
            PHP_EOL,
            $iterator->forBladeFiles(),
            $iterator->forRoutes(),
            PHP_EOL,
        ]);
    }
}
