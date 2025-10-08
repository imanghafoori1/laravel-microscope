<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Imanghafoori\LaravelMicroscope\Checks\CheckRubySyntax;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class CheckEndIfCommand extends BaseCommand
{
    protected $signature = 'check:endif
    {--f|file=}
    {--d|folder=}
    {--t|test : backup the changed files}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $description = 'replaces ruby like syntax of php (endif) with curly brackets.';

    public $checks = [CheckRubySyntax::class];

    public $initialMsg = 'Checking for endif\'s...';

    public $customMsg = 'No ruby syntax found. \(^_^)/';

    public $gitConfirm = true;

    public function handleCommand()
    {
        $this->printAll($this->forComposerLoadedFiles());
    }
}
