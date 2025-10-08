<?php

namespace Imanghafoori\LaravelMicroscope\Features\FacadeAlias;

use Closure;
use Illuminate\Foundation\AliasLoader;
use Imanghafoori\LaravelMicroscope\Features\EnforceImports\EnforceImports;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class CheckAliasesCommand extends BaseCommand
{
    protected $signature = 'check:aliases
    {--f|file= : Comma separated list of file names to search in}
    {--d|folder= : Comma separated list of folders to search in}
    {--F|except-file= : Comma separated list of file names to avoid}
    {--D|except-folder= : Comma separated list of folder names to avoid}
    {--a|alias= : Comma separated list of aliases to look for}
    {--s|nofix : avoids the automatic fixes}
    ';

    protected $description = 'Replaces facade aliases with full namespace';

    public $customMsg = '✅  Finished checking for facade aliases.';

    public $initialMsg = ' 🔍 Looking Facade Aliases...';

    public $checks = [EnforceImports::class, FacadeAliasesCheck::class];

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        FacadeAliasesCheck::$command = $this->getOutput();

        if ($this->option('nofix')) {
            FacadeAliasesCheck::$handler = FacadeAliasReporter::class;
        }

        $importsProvider = self::getParamProvider();
        self::setEnforceImportsOptions($importsProvider);
        self::setFacadeAliasCheckOptions($this->option('alias'));
        FacadeAliasesCheck::$aliases = AliasLoader::getInstance()->getAliases();
        FacadeAliasesCheck::$importsProvider = $importsProvider;
        $iterator->formatPrintPsr4Classmap();

        return FacadeAliasReporter::$errorCount > 0 ? 1 : 0;
    }

    private static function setFacadeAliasCheckOptions($alias)
    {
        $alias = ltrim($alias, '=');
        $alias && (FacadeAliasesCheck::$alias = explode(',', strtolower($alias)));
    }

    private static function setEnforceImportsOptions(Closure $paramProvider)
    {
        $aliases = AliasLoader::getInstance()->getAliases();

        $aliasKeys = array_map(fn ($alias) => '\\'.$alias, array_keys($aliases));

        $onError = fn () => null;

        $mutator = fn ($class) => ltrim($aliases[$class] ?? $class, '\\');

        EnforceImports::setOptions(false, $aliasKeys, $paramProvider, $onError, $mutator);
    }

    private static function getParamProvider()
    {
        return function (PhpFileDescriptor $file) {
            $imports = ParseUseStatement::parseUseStatements($file->getTokens());

            return $imports[0] ?: [$imports[1]];
        };
    }
}
