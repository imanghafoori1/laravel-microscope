<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDeadControllers;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedPsr4Classes;

class CheckDeadControllers extends BaseCommand
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

    public function handleCommand()
    {
        $checkSet = CheckSet::initParam($this->checks);
        $psr4Stats = ForAutoloadedPsr4Classes::check($checkSet);

        Psr4Report::formatAndPrintAutoload($psr4Stats, [], $this->getOutput());
    }
}
