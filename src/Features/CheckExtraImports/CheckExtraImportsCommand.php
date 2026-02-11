<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraImports;

use Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Checks\CheckForExtraImports;
use Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Handlers\ExtraImportsHandler;
use Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Cache;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Foundations\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\Foundations\Reports\FilesStats;

class CheckExtraImportsCommand extends BaseCommand
{
    use FilesStats;

    protected $signature = 'check:extra_imports
        {--force : fixes without asking}
        {--f|file= : Pattern for file names to scan}
        {--F|except-file= : Pattern for file names to avoid}
        {--D|except-folder= : Pattern for folder names to avoid}
        {--d|folder= : Pattern for file names to scan}
        {--s|nofix : avoids the automatic fixes}
    ';

    protected $description = 'Checks the validity of use statements';

    protected $customMsg = '';

    /**
     * @var array<int, class-string<\Imanghafoori\LaravelMicroscope\Check>>
     */
    protected $checks = [
        CheckForExtraImports::class,
    ];

    public $initialMsg = 'Checking imports and class references...';

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return int
     */
    public function handleCommand($iterator)
    {
        CheckForExtraImports::$importsCount = 0;
        ExtraImportsHandler::$count = 0;

        $pathDTO = PathFilterDTO::makeFromOption($this);
        Cache::loadToMemory('check_extra_imports');

        CheckForExtraImports::setImports();

        /**
         * @var string[] $messages
         */
        $messages = [
            CheckImportReporter::totalImportsMsg(),
            $iterator->forComposerLoadedFiles(),
            PHP_EOL,
            CheckImportReporter::header(),
            PHP_EOL,
            self::getFilesStats(),
            PHP_EOL,
            $iterator->forBladeFiles(),
            PHP_EOL,
            $iterator->forMigrationsAndConfigs(),
            $iterator->forRoutes(),
            PHP_EOL,
        ];

        $iterator->printAll($messages);
        // must be after other messages:
        $iterator->printAll(Reporters\SummeryReport::summery(CheckForExtraImports::$importsCount));

        if (! CheckForExtraImports::$importsCount) {
            $filter = $pathDTO->includeFile ?: $pathDTO->includeFolder;
            $this->getOutput()->writeln(Reporters\SummeryReport::noImportsFound($filter));
        }
        Cache::writeCacheContent();

        $this->line('');
        $count = ExtraImportsHandler::$count;

        // reset static vars to avoid testing issues.
        ExtraImportsHandler::$count = 0;
        CheckForExtraImports::$importsCount = 0;

        return $count > 0 ? 1 : 0;
    }
}
