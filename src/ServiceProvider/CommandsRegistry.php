<?php

namespace Imanghafoori\LaravelMicroscope\ServiceProvider;

use Imanghafoori\LaravelMicroscope\Commands;
use Imanghafoori\LaravelMicroscope\Features;
use Imanghafoori\LaravelMicroscope\SearchReplace;

trait CommandsRegistry
{
    private static $commandNames = [
        Features\CheckFacadeDocblocks\CheckFacadeDocblocks::class,
        Features\CheckEvents\CheckEvents::class,
        Commands\CheckGates::class,
        Commands\CheckRoutes::class,
        Features\CheckView\CheckViewsCommand::class,
        Features\Psr4\CheckPsr4ArtisanCommand::class,
        Features\CheckImports\CheckImportsCommand::class,
        Features\FacadeAlias\CheckAliasesCommand::class,
        Commands\CheckAll::class,
        Features\CheckClassyStrings\ClassifyStrings::class,
        Features\CheckDD\CheckDDCommand::class,
        Commands\CheckEarlyReturns::class,
        Commands\CheckCompact::class,
        Commands\CheckBladeQueries::class,
        Features\ActionComments\CheckActionComments::class,
        Features\CheckEnvCalls\CheckEnvCallsCommand::class,
        Features\ExtractsBladePartials\CheckExtractBladeIncludesCommand::class,
        Commands\PrettyPrintRoutes::class,
        Features\ServiceProviderGenerator\CheckCodeGeneration::class,
        Features\CheckDeadControllers\CheckDeadControllers::class,
        Features\CheckGenericDocBlocks\CheckGenericDocBlocksCommand::class,
        Commands\CheckPsr12::class,
        Commands\CheckEndIf::class,
        Commands\EnforceQuery::class,
        Commands\EnforceHelpers::class,
        SearchReplace\CheckRefactorsCommand::class,
        Commands\CheckDynamicWhereMethod::class,
        Features\ListModels\ListModelsArtisanCommand::class,
        Commands\CheckEmptyComments::class,
        Commands\CheckExtraSemiColons::class,
    ];

    private function registerCommands()
    {
        $this->commands(self::$commandNames);
    }
}
