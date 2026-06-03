<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEndIf;

use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class CheckEndIfCommand extends BaseCommand
{
    protected $signature = 'check:endif
    {--f|file=}
    {--d|folder=}
    {--s|nofix : Does not tamper with the file contents and only inspects them.}
    {--a|force-fix : Does not ask you to confirm for each and every file.}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $description = 'replaces ruby like syntax of php (endif) with curly brackets.';

    public $checks = [CheckEndIfSyntax::class];

    public $initialMsg = 'Checking for endif\'s...';

    public $customMsg = 'No ruby syntax found. \(^_^)/';

    public $gitConfirm = true;

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        CheckEndIfSyntax::$ask = ! $this->option('force-fix');
        CheckEndIfSyntax::$nofix = $this->option('nofix');

        $iterator->printAll([
            $iterator->forComposerLoadedFiles(),
            PHP_EOL,
            $iterator->forRoutes(),
            PHP_EOL,
        ]);
    }
}
