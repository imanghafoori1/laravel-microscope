<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraFQCN;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\Reports\FilesStats;
use Imanghafoori\LaravelMicroscope\Foundations\UseStatementParser;

class CheckExtraFQCNCommand extends BaseCommand
{
    use FilesStats;

    protected $signature = 'check:extra_fqcn
        {--fix : Fix references}
        {--class= : Only fixes references of the specified class names}
        {--f|file= : Pattern for file names to scan}
        {--d|folder= : Pattern for file names to scan}
        {--F|except-file= : Pattern for file names to avoid}
        {--D|except-folder= : Pattern for folder names to avoid}';

    protected $description = 'Checks for unnecessary FQCNs.';

    protected $customMsg = 'No Unnecessary Fully Qualified Class Name found.  \(^_^)/';

    public $initialMsg = PHP_EOL.'Checking for fully qualified class names.';

    public $checks = [ExtraFQCN::class];

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        $options = $this->options;

        ExtraFQCN::configure(
            $options->option('class'),
            $options->option('fix'),
            UseStatementParser::get()
        );

        $iterator->printAll([
            CheckImportReporter::totalImportsMsg(),
            $iterator->forComposerLoadedFiles(),
            PHP_EOL,
            CheckImportReporter::header(),
            PHP_EOL,
            self::getFilesStats(),
            $iterator->forMigrationsAndConfigs(),
            $iterator->forRoutes(),
            PHP_EOL,
        ]);

        ExtraFQCN::reset();
        ! $options->option('fix') && ($this->exitCode() === 1) && $this->printGuide();
    }

    private function printGuide()
    {
        $this->line(Color::yellow(' You may use `--fix` option to delete extra code, run:'));
        $this->line(' php artisan '.Color::yellow('check:extra_fqcn --fix'));
    }
}
