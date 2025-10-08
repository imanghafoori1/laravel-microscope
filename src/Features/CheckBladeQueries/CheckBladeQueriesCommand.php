<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckBladeQueries;

use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class CheckBladeQueriesCommand extends BaseCommand
{
    protected $signature = 'check:blade_queries
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    {--detailed : Show files being checked}
    ';

    protected $description = 'Checks db queries in blade files';

    public $checks = [IsQueryCheck::class];

    public $initialMsg = 'Checking blade files for db queries...';

    public $customMsg = 'No queries found in blade files.';

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        // checks the blade files for database queries.
        $iterator->printAll([$iterator->forBladeFiles()]);
    }
}
