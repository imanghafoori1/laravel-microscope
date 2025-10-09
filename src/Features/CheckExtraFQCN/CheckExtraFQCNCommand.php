<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraFQCN;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\ChecksOnPsr4Classes;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;
use JetBrains\PhpStorm\Pure;

class CheckExtraFQCNCommand extends BaseCommand
{
    protected $signature = 'check:fqcn
        {--fix : Fix references}
        {--class= : Fix references of the specified class}
        {--f|file= : Pattern for file names to scan}
        {--d|folder= : Pattern for file names to scan}
        {--F|except-file= : Pattern for file names to avoid}
        {--D|except-folder= : Pattern for folder names to avoid}';

    protected $description = 'Checks for unnecessary FQCNs.';

    protected $customMsg = 'No Unnecessary Fully Qualified Class Name found.  \(^_^)/';

    public $initialMsg = PHP_EOL.'Checking class references...';

    public $checks = [ExtraFQCN::class];

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        $fix = $this->options->option('fix');
        $class = $this->options->option('class');

        ExtraFQCN::$class = $class;
        ExtraFQCN::$fix = $fix;
        ExtraFQCN::$imports = self::useStatementParser();

        $iterator->printAll([
            CheckImportReporter::totalImportsMsg(),
            $iterator->forComposerLoadedFiles(),
            PHP_EOL.CheckImportReporter::header(),
            PHP_EOL.self::getFilesStats(),
            $iterator->forMigrationsAndConfigs(),
            $iterator->forRoutes(),
        ]);

        ! $fix && $this->exitCode() === 1 && $this->printGuide();
    }

    #[Pure]
    private static function useStatementParser()
    {
        return function (PhpFileDescriptor $file) {
            $imports = ParseUseStatement::parseUseStatements($file->getTokens());

            return $imports[0] ?: [$imports[1]];
        };
    }

    #[Pure]
    private static function getFilesStats(): string
    {
        $filesCount = ChecksOnPsr4Classes::$checkedFilesCount;

        return $filesCount ? CheckImportReporter::getFilesStats($filesCount) : '';
    }

    private function printGuide()
    {
        $this->line('<fg=yellow> You may use `--fix` option to delete extra code:</>');
        $this->line('<fg=yellow> php artisan check:fqcn --fix</>');
    }
}
