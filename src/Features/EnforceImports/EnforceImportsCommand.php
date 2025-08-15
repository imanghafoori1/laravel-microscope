<?php

namespace Imanghafoori\LaravelMicroscope\Features\EnforceImports;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Psr4ReportPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedClassMaps;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedFiles;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedPsr4Classes;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;
use JetBrains\PhpStorm\Pure;

class EnforceImportsCommand extends Command
{
    use LogsErrors;

    protected $signature = 'enforce:imports
        {--class= : Fix references of the specified class}
        {--f|file= : Pattern for file names to scan}
        {--d|folder= : Pattern for file names to scan}
        {--F|except-file= : Pattern for file names to avoid}
        {--D|except-folder= : Pattern for folder names to avoid}';

    protected $description = 'Enforces the imports to be at the top.';

    protected $customMsg = 'All the class references are imported.  \(^_^)/';

    public function handle()
    {
        event('microscope.start.command');
        $this->line('');
        $this->info('Checking class references...');

        $pathDTO = PathFilterDTO::makeFromOption($this);

        $class = $this->option('class');

        $useStatementParser = [self::useStatementParser(), $class];

        $checks = [EnforceImports::class];

        $classMapStats = ForAutoloadedClassMaps::check(base_path(), $checks, $useStatementParser, $pathDTO);
        $autoloadedFiles = ForAutoloadedFiles::check(base_path(), $checks, $useStatementParser, $pathDTO);
        $psr4Stats = ForAutoloadedPsr4Classes::check($checks, $useStatementParser, $pathDTO);

        $errorPrinter = ErrorPrinter::singleton($this->output);

        $consoleOutput = Psr4Report::getConsoleMessages($psr4Stats, $classMapStats, $autoloadedFiles);

        $messages = self::getMessages($consoleOutput);

        Psr4ReportPrinter::printAll($messages, $this->getOutput());

        $this->finishCommand($errorPrinter);

        $errorPrinter->printTime();

        CachedFiles::writeCacheFiles();

        return self::hasError() > 0 ? 1 : 0;
    }

    #[Pure]
    private static function useStatementParser()
    {
        return function (PhpFileDescriptor $file) {
            $imports = ParseUseStatement::parseUseStatements($file->getTokens());

            return $imports[0] ?: [$imports[1]];
        };
    }

    private static function getMessages($autoloadStats)
    {
        return [
            CheckImportReporter::totalImportsMsg(),
            $autoloadStats,
        ];
    }

    private static function hasError()
    {
        return isset(ErrorPrinter::singleton()->errorsList['enforce_imports']);
    }
}
