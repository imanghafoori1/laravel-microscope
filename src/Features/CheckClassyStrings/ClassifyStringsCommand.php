<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckClassyStrings;

use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class ClassifyStringsCommand extends BaseCommand
{
    protected $signature = 'check:stringy_classes
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $description = 'Replaces string references with ::class version of them.';

    public $initialMsg = 'Checking stringy classes...';

    public $checks = [CheckStringy::class];

    public $customMsg = '';

    public function handleCommand($iterator)
    {
        CheckStringy::$command = $this;

        $iterator->printAll([
            $iterator->forComposerLoadedFiles(),
            $iterator->forRoutes(),
            PHP_EOL.$iterator->forBladeFiles(),
        ]);
    }
}
