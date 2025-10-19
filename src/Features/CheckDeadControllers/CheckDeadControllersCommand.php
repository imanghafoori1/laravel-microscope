<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDeadControllers;

use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class CheckDeadControllersCommand extends BaseCommand
{
    protected $signature = 'check:dead_controllers
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $customMsg = 'No dead Controller Action was found!   \(^_^)/';

    protected $description = 'Checks that public controller methods have routes.';

    public $initialMsg = 'Checking for route-less controllers...';

    public $checks = [RoutelessControllerActions::class];

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        $iterator->formatPrintPsr4();
    }
}
