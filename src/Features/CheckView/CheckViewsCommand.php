<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckView;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\BladeReport;
use Imanghafoori\LaravelMicroscope\Features\CheckView\Check\CheckView;
use Imanghafoori\LaravelMicroscope\Features\CheckView\Check\CheckViewFilesExistence;
use Imanghafoori\LaravelMicroscope\Features\CheckView\Check\CheckViewStats;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;

class CheckViewsCommand extends BaseCommand
{
    protected $signature = 'check:views
        {--detailed : Show files being checked}
        {--f|file=}
        {--d|folder=}
        {--F|except-file= : Comma seperated patterns for file names to avoid}
        {--D|except-folder= : Comma seperated patterns for folder names to avoid}
';

    protected $description = 'Checks the validity of blade files';

    public $initialMsg = 'Checking views...';

    public $customMsg = '...'.PHP_EOL.'- All view() calls are correct!';

    public $checks = [CheckView::class];

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        $lines = $iterator->forComposerLoadedFiles();
        $lines->add(PHP_EOL.$iterator->forRoutes());

        $checkSet = CheckSet::initParams([CheckViewFilesExistence::class], $this);
        $lines->add(PHP_EOL.BladeReport::getBladeStats(ForBladeFiles::check($checkSet)));

        $iterator->printAll($lines);

        $this->getOutput()->writeln($this->stats(
            CheckViewStats::$checkedCallsCount,
            CheckViewStats::$skippedCallsCount
        ));
    }

    private function stats($checkedCallsCount, $skippedCallsCount): string
    {
        return ' - '.$checkedCallsCount.' view references were checked to exist. ('.$skippedCallsCount.' skipped)';
    }
}
