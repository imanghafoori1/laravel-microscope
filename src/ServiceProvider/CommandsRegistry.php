<?php

namespace Imanghafoori\LaravelMicroscope\ServiceProvider;

use Imanghafoori\LaravelMicroscope\Commands;
use Imanghafoori\LaravelMicroscope\Features;
use Imanghafoori\LaravelMicroscope\SearchReplace;

trait CommandsRegistry
{
    private static $commandNames = [
        Features\CheckFacadeDocblocks\CheckFacadeDocblocksCommand::class,
        Features\CheckEvents\CheckEventsCommand::class,
        Commands\CheckGates::class,
        Commands\CheckRoutes::class,
        Features\CheckView\CheckViewsCommand::class,
        Features\Psr4\Console\CheckPsr4ArtisanCommand::class,
        Features\CheckImports\CheckImportsCommand::class,
        Features\FacadeAlias\CheckAliasesCommand::class,
        Commands\CheckAll::class,
        Features\CheckClassyStrings\ClassifyStringsCommand::class,
        Features\CheckDD\CheckDDCommand::class,
        Commands\CheckEarlyReturnsCommand::class,
        Commands\CheckCompact::class,
        Features\CheckBladeQueries\CheckBladeQueriesCommand::class,
        Features\ActionComments\CheckActionCommentsCommand::class,
        Features\CheckEnvCalls\CheckEnvCallsCommand::class,
        Features\ExtractsBladePartials\CheckExtractBladeIncludesCommand::class,
        Commands\PrettyPrintRoutes::class,
        Features\ServiceProviderGenerator\CheckCodeGeneration::class,
        Features\CheckDeadControllers\CheckDeadControllersCommand::class,
        Features\CheckGenericDocBlocks\CheckGenericDocBlocksCommand::class,
        Features\CheckPsr12\CheckPsr12Command::class,
        Commands\CheckEndIfCommand::class,
        Commands\EnforceQuery::class,
        Commands\EnforceHelpers::class,
        SearchReplace\CheckRefactorsCommand::class,
        Commands\CheckDynamicWhereMethod::class,
        Features\ListModels\ListModelsArtisanCommand::class,
        Commands\CheckEmptyComments::class,
        Commands\CheckExtraSemiColons::class,
        Commands\EnforceArrowFunctions::class,
        Commands\AnonymizeMigrations::class,
        Features\CheckExtraFQCN\CheckExtraFQCNCommand::class,
        Features\EnforceImports\EnforceImportsCommand::class,
        Commands\CheckStatsCommand::class,
        Commands\CheckAbortIf::class,
    ];

    private function registerCommands()
    {
        $this->commands(self::$commandNames);
    }
}
