<?php

namespace Imanghafoori\LaravelMicroscope\Features\FacadeAlias;

use Illuminate\Console\Command;
use Illuminate\Foundation\AliasLoader;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\Features\EnforceImports\EnforceImports;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedClassMaps;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedPsr4Classes;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class CheckAliasesCommand extends Command
{
    use LogsErrors;

    protected $signature = 'check:aliases
    {--f|file= : Comma separated list of file names to search in}
    {--d|folder= : Comma separated list of folders to search in}
    {--F|except-file= : Comma separated list of file names to avoid}
    {--D|except-folder= : Comma separated list of folder names to avoid}
    {--a|alias= : Comma separated list of aliases to look for}
    {--s|nofix : avoids the automatic fixes}
    ';

    protected $description = 'Replaces facade aliases with full namespace';

    protected $finishMsg = '✅  Finished checking for facade aliases.';

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info(' 🔍 Looking Facade Aliases...');

        $errorPrinter->printer = $this->output;

        $pathDTO = PathFilterDTO::makeFromOption($this);
        FacadeAliasesCheck::$command = $this->getOutput();

        if ($this->option('nofix')) {
            FacadeAliasesCheck::$handler = FacadeAliasReporter::class;
        }

        $this->handleAliasOption();
        $paramProvider = function (PhpFileDescriptor $file) {
            $imports = ParseUseStatement::parseUseStatements($file->getTokens());

            return $imports[0] ?: [$imports[1]];
        };

        $aliases = AliasLoader::getInstance()->getAliases();
        $aliaseKeys = array_map(function ($alias) {
            return '\\'.$alias;
        }, array_keys($aliases));

        $onError = function () {
            //
        };

        $mutator = function ($class) use ($aliases) {
            return ltrim($aliases[$class] ?? $class, '\\');
        };

        EnforceImports::setOptions(false, $aliaseKeys, $paramProvider, $onError, $mutator);

        $check = [EnforceImports::class, FacadeAliasesCheck::class];
        $psr4Stats = ForAutoloadedPsr4Classes::check($check, $paramProvider, $pathDTO);
        $classMapStats = ForAutoloadedClassMaps::check(base_path(), $check, $paramProvider, $pathDTO);

        Psr4Report::formatAndPrintAutoload($psr4Stats, $classMapStats, $this->getOutput());

        $this->info(PHP_EOL.' '.$this->finishMsg);

        $errorPrinter->printTime();

        return FacadeAliasReporter::$errorCount > 0 ? 1 : 0;
    }

    private function handleAliasOption(): void
    {
        $alias = ltrim($this->option('alias'), '=');
        if ($alias) {
            FacadeAliasesCheck::$alias = explode(',', strtolower($alias));
        }
    }
}
