<?php

namespace Imanghafoori\LaravelMicroscope\Features\EnforceImports;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;
use JetBrains\PhpStorm\Pure;

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

    public $checks = [EnforceImports::class];

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        $noFix = $this->options->option('no-fix');
        $class = $this->options->option('class');
        EnforceImports::setOptions($noFix, $class, self::useParser(), self::getOnError($noFix));

        $iterator->printAll([
            CheckImportReporter::totalImportsMsg(),
            $iterator->forComposerLoadedFiles(),
        ]);
    }

    #[Pure]
    private static function useParser()
    {
        return function (PhpFileDescriptor $file) {
            $imports = ParseUseStatement::parseUseStatements($file->getTokens());

            return $imports[0] ?: [$imports[1]];
        };
    }

    private static function getOnError($noFix)
    {
        if ($noFix) {
            $header = 'FQCN needs to be imported';
        } else {
            $header = 'FQCN got imported at the top';
        }

        return function ($classRef, $file, $line) use ($header) {
            ErrorPrinter::singleton()->simplePendError($classRef, $file, $line, 'enforce_imports', $header);
        };
    }
}
