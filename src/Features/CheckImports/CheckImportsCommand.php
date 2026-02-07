<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassAtMethod;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\PrintWrongClassRefs;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Foundations\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\Foundations\Reports\FilesStats;
use Imanghafoori\LaravelMicroscope\Foundations\UseStatementParser;
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

        Cache::loadToMemory('check_imports.php');

        $pathDTO = PathFilterDTO::makeFromOption($this);

        CheckClassReferencesAreValid::$importsProvider = UseStatementParser::get();
        ImportsAnalyzer::$checkedRefCount = 0;

        /**
         * @var string[] $messages
         */
        $messages = [
            CheckImportReporter::totalImportsMsg(),
            $iterator->forComposerLoadedFiles(),
            PHP_EOL.CheckImportReporter::header(),
            PHP_EOL.self::getFilesStats(),
            PHP_EOL.$iterator->forBladeFiles().PHP_EOL,
            $iterator->forMigrationsAndConfigs(),
            $iterator->forRoutes(),
        ];

        $iterator->printAll($messages);
        // must be after other messages:
        $counter = ErrorCounter::calculateErrors(ErrorPrinter::singleton()->errorsList);

        $iterator->printAll([PHP_EOL, Reporters\SummeryReport::summery($counter)]);

        if (! ImportsAnalyzer::$checkedRefCount) {
            $filter = $pathDTO->includeFile ?: $pathDTO->includeFolder;
            $this->getOutput()->writeln(Reporters\SummeryReport::noImportsFound($filter));
        }

        Cache::writeCacheContent();

        $this->line('');

        return $counter->getTotalErrors() > 0 ? 1 : 0;
    }
}
