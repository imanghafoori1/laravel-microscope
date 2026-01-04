<?php

namespace Imanghafoori\LaravelMicroscope\ServiceProvider;

use Imanghafoori\LaravelMicroscope\Features;

trait CommandsRegistry
{
    private static $commandNames = [
        Features\CheckFacadeDocblocks\CheckFacadeDocblocksCommand::class,
        Features\CheckEvents\CheckEventsCommand::class,
        Features\CheckGates\CheckGates::class,
        Features\CheckRoutes\CheckRoutes::class,
        Features\CheckView\CheckViewsCommand::class,
        Features\Psr4\Console\CheckPsr4ArtisanCommand::class,
        Features\CheckImports\CheckImportsCommand::class,
        Features\FacadeAlias\CheckAliasesCommand::class,
        Features\CheckAll::class,
        Features\CheckClassyStrings\ClassifyStringsCommand::class,
        Features\CheckDD\CheckDDCommand::class,
        Features\CheckEarlyReturns\CheckEarlyReturnsCommand::class,
        Features\CheckCompact::class,
        Features\CheckBladeQueries\CheckBladeQueriesCommand::class,
        Features\ActionComments\CheckActionCommentsCommand::class,
        Features\CheckEnvCalls\CheckEnvCallsCommand::class,
        Features\ExtractsBladePartials\CheckExtractBladeIncludesCommand::class,
        Features\ServiceProviderGenerator\CheckCodeGeneration::class,
        Features\CheckDeadControllers\CheckDeadControllersCommand::class,
        Features\CheckGenericDocBlocks\CheckGenericDocBlocksCommand::class,
        Features\CheckPsr12\CheckPsr12Command::class,
        Features\CheckEndIf\CheckEndIfCommand::class,
        Features\SearchReplace\Commands\EnforceQuery::class,
        Features\SearchReplace\Commands\EnforceHelpers::class,
        Features\SearchReplace\CheckRefactorsCommand::class,
        Features\SearchReplace\Commands\CheckDynamicWhereMethod::class,
        Features\ListModels\ListModelsArtisanCommand::class,
        Features\SearchReplace\Commands\CheckEmptyComments::class,
        Features\SearchReplace\Commands\CheckExtraSemiColons::class,
        Features\SearchReplace\Commands\EnforceArrowFunctions::class,
        Features\SearchReplace\Commands\AnonymizeMigrations::class,
        Features\CheckExtraFQCN\CheckExtraFQCNCommand::class,
        Features\EnforceImports\EnforceImportsCommand::class,
        Features\CheckStatsCommand::class,
        Features\SearchReplace\Commands\CheckAbortIf::class,
    ];

    private function registerCommands()
    {
        $this->commands(self::$commandNames);
    }
}
