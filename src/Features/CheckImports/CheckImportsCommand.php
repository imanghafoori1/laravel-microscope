<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Checks\CheckForExtraImports;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassAtMethod;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\PrintWrongClassRefs;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Foundations\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\Foundations\Reports\FilesStats;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class CheckImportsCommand extends BaseCommand
{
    use FilesStats;

    protected $signature = 'check:imports
        {--force : fixes without asking}
        {--w|wrong : This flag is deprecated and has no effect.}
        {--e|extra : This flag is deprecated and has no effect.}
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
        CheckClassReferencesAreValid::class,
        CheckClassAtMethod::class,
    ];

    public $initialMsg = 'Checking imports and class references...';

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return int
     */
    public function handleCommand($iterator)
    {
        CheckClassAtMethod::$handler::$fix = ! $this->option('nofix');
        if ($this->option('nofix')) {
            CheckClassReferencesAreValid::$wrongClassRefsHandler = PrintWrongClassRefs::class;
        }

        if ($this->option('extra')) {
            $this->checkSet->checks->checks = [CheckForExtraImports::class];
        }

        if ($this->option('wrong')) {
            unset($this->checkSet->checks->checks[0]);
        }

        Cache::loadToMemory('check_imports');
        Cache::loadToMemory('check_extra_imports');

        $pathDTO = PathFilterDTO::makeFromOption($this);

        ImportsAnalyzer::$checkedRefCount = 0;

        /**
         * @var string[] $messages
         */
        $messages = [
            CheckImportReporter::totalImportsMsg(),
            PHP_EOL,
            $iterator->forComposerLoadedFiles(),
            PHP_EOL,
            CheckImportReporter::header(),
            self::getFilesStats(),
            $iterator->forBladeFiles(),
            $iterator->forMigrationsAndConfigs(),
            $iterator->forRoutes(),
            PHP_EOL,
        ];

        $iterator->printAll($messages);
        // must be after other messages:
        $counter = ImportsErrorCounter::calculateErrors(ErrorPrinter::singleton()->errorsList);


        if (! ImportsAnalyzer::$checkedRefCount && ! CheckForExtraImports::$importsCount) {
            $filter = $pathDTO->includeFile ?: $pathDTO->includeFolder;
            $this->getOutput()->writeln(
                Reporters\SummeryReport::noImportsFound($filter)
            );
        } else {
            $iterator->printAll([PHP_EOL, Reporters\SummeryReport::summery($counter)]);
        }

        Cache::writeCacheContent();

        $this->line('');

        return $counter->getTotalErrors() > 0 ? 1 : 0;
    }
}
