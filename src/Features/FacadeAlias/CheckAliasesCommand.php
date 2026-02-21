<?php

namespace Imanghafoori\LaravelMicroscope\Features\FacadeAlias;

use Illuminate\Foundation\AliasLoader;
use Imanghafoori\LaravelMicroscope\Features\EnforceImports\EnforceImports;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

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
    public function handleCommand($iterator, $command)
    {
        if ($this->option('nofix')) {
            $this->checkSet->checks->checks = [FacadeAliasesCheck::class];
            FacadeAliasesCheck::$handler = FacadeAliasReporter::class;
        }

        self::setEnforceImportsOptions();
        self::setFacadeAliasCheckOptions($command->option('alias'));
        FacadeAliasesCheck::$aliases = AliasLoader::getInstance()->getAliases();

        $iterator->formatPrintPsr4Classmap();
        $iterator->forComposerLoadedFiles();
        $iterator->forRoutes();
    }

    private static function setFacadeAliasCheckOptions($alias)
    {
        $alias = ltrim($alias, '=');
        $alias && (FacadeAliasesCheck::$alias = explode(',', strtolower($alias)));
    }

    private static function setEnforceImportsOptions()
    {
        $aliases = AliasLoader::getInstance()->getAliases();

        $aliasKeys = array_map(fn ($alias) => '\\'.$alias, array_keys($aliases));

        $onError = fn () => null;

        $mutator = fn ($class) => ltrim($aliases[$class] ?? $class, '\\');

        EnforceImports::setOptions(false, $aliasKeys, $onError, $mutator);
    }
}
