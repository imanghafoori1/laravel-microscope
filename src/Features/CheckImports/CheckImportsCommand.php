<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\PrintWrongClassRefs;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\ChecksOnPsr4Classes;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;
use JetBrains\PhpStorm\Pure;

class CheckImportsCommand extends BaseCommand
{
    protected $signature = 'check:imports
        {--force : fixes without asking}
        {--w|wrong : Only reports wrong imports}
        {--e|extra : Only reports extra imports}
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
    ];

    public $initialMsg = 'Checking imports and class references...';

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return int
     */
    public function handleCommand($iterator)
    {
        if ($this->option('nofix')) {
            CheckClassReferencesAreValid::$wrongClassRefsHandler = PrintWrongClassRefs::class;
        }

        if (file_exists($path = CachedFiles::getFolderPath().'check_imports.php')) {
            CheckClassReferencesAreValid::$cache = (require $path) ?: [];
        }

        if ($this->option('wrong')) {
            CheckClassReferencesAreValid::$checkExtra = false;
        }

        if ($this->option('extra')) {
            CheckClassReferencesAreValid::$checkWrong = false;
        }

        $pathDTO = PathFilterDTO::makeFromOption($this);

        CheckClassReferencesAreValid::$imports = self::useStatementParser();

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
        $iterator->printAll([PHP_EOL.Reporters\SummeryReport::summery(ErrorPrinter::singleton()->errorsList)]);

        if (! ImportsAnalyzer::$checkedRefCount) {
            $messages = '<options=bold;fg=yellow>No imports were found!</> with filter: <fg=red>"'.($pathDTO->includeFile ?: $pathDTO->includeFolder).'"</>';
            $this->getOutput()->writeln($messages);
        }

        if ($cache = CheckClassReferencesAreValid::$cache) {
            self::writeCacheContent($cache);
        }

        $this->line('');

        return ErrorCounter::getTotalErrors() > 0 ? 1 : 0;
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

    private static function writeCacheContent(array $cache): void
    {
        $folder = CachedFiles::getFolderPath();
        ! is_dir($folder) && mkdir($folder);
        $content = CachedFiles::getCacheFileContents($cache);
        $path = $folder.'check_imports.php';
        file_exists($path) && chmod($path, 0777);
        file_put_contents($path, $content);
    }
}
