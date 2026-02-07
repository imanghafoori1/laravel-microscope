<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraImports;

use Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Checks\CheckImportsAreUsed;
use Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Handlers\ExtraImports;
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
        CheckImportsAreUsed::class,
    ];

    public $initialMsg = 'Checking imports and class references...';

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return int
     */
    public function handleCommand($iterator)
    {
        CheckImportsAreUsed::$importsCount = 0;
        $pathDTO = PathFilterDTO::makeFromOption($this);
        Cache::loadToMemory('check_extra_imports.php');

        CheckImportsAreUsed::setImports();

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
        $iterator->printAll(Reporters\SummeryReport::summery(CheckImportsAreUsed::$importsCount));

        if (! CheckImportsAreUsed::$importsCount) {
            $filter = $pathDTO->includeFile ?: $pathDTO->includeFolder;
            $this->getOutput()->writeln(Reporters\SummeryReport::noImportsFound($filter));
        }
        Cache::writeCacheContent();

        $this->line('');

        return ExtraImports::$count > 0 ? 1 : 0;
    }
}
