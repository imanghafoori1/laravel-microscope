<?php

namespace Imanghafoori\LaravelMicroscope\Features\EnforceImports;

use Imanghafoori\LaravelMicroscope\Features\CheckExtraFQCN\ExtraFQCNCheck;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class EnforceImportsCommand extends BaseCommand
{
    protected $signature = 'enforce:imports
        {--no-fix : avoid changing the files}
        {--class= : Fix references of the specified class}
        {--f|file= : Pattern for file names to scan}
        {--d|folder= : Pattern for file names to scan}
        {--F|except-file= : Pattern for file names to avoid}
        {--D|except-folder= : Pattern for folder names to avoid}';

    protected $description = 'Enforces the imports to be at the top.';

    protected $customMsg = 'All the class references are imported.  \(^_^)/';

    public $initialMsg = 'Checking class references...';

    public $checks = [
        ExtraFQCNCheck::class,
        EnforceImportsCheck::class,
    ];

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        EnforceImportsCheck::setOptions(
            $this->options->option('no-fix'),
            $this->options->option('class'),
        );

        $iterator->printAll([
            CheckImportReporter::importsCheckedMsg(),
            $iterator->forComposerLoadedFiles(),
        ]);
    }
}
