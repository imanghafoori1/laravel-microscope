<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ImanGhafoori\ComposerJson\ComposerJson as Composer;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ConsolePrinterInstaller;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckEvents\Installer;
use Imanghafoori\LaravelMicroscope\Features\CheckUnusedBladeVars\UnusedVarsInstaller;
use Imanghafoori\LaravelMicroscope\Features\CheckView\Check\CheckView;
use Imanghafoori\LaravelMicroscope\Features\ListModels\ListModelsArtisanCommand;
use Imanghafoori\LaravelMicroscope\FileReaders\PhpFinder;
use Imanghafoori\LaravelMicroscope\SpyClasses\SpyBladeCompiler;
use Imanghafoori\LaravelMicroscope\SpyClasses\SpyGate;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class LaravelMicroscopeServiceProvider extends ServiceProvider
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
        Commands\ClassifyStrings::class,
        Features\CheckDD\CheckDDCommand::class,
        Commands\CheckEarlyReturns::class,
        Commands\CheckCompact::class,
        Commands\CheckBladeQueries::class,
        Features\ActionComments\CheckActionComments::class,
        Features\CheckEnvCalls\CheckEnvCallsCommand::class,
        Features\ExtractsBladePartials\CheckExtractBladeIncludesCommand::class,
        Commands\PrettyPrintRoutes::class,
        Features\ServiceProviderGenerator\CheckCodeGeneration::class,
        Commands\CheckDeadControllers::class,
        Features\CheckGenericDocBlocks\CheckGenericDocBlocksCommand::class,
        Commands\CheckPsr12::class,
        Commands\CheckEndIf::class,
        Commands\EnforceQuery::class,
        Commands\EnforceHelpers::class,
        SearchReplace\CheckRefactorsCommand::class,
        Commands\CheckDynamicWhereMethod::class,
        ListModelsArtisanCommand::class,
    ];

    public function boot()
    {
        UnusedVarsInstaller::spyView();

        if (! $this->canRun()) {
            return;
        }

        $this->loadViewsFrom(__DIR__.'/../templates', 'microscope_package');

        Event::listen('microscope.start.command', function () {
            ! defined('microscope_start') && define('microscope_start', microtime(true));
        });

        $this->resetCountersOnFinish();

        $this->commands(self::$commandNames);

        ErrorPrinter::$ignored = config('microscope.ignore');

        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('microscope.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../templates' => base_path('resources/views/vendor/microscope'),
        ], 'microscope');

        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'microscope');

        ConsolePrinterInstaller::boot();
    }

    public function register()
    {
        if (! $this->canRun()) {
            return;
        }

        ComposerJson::$composer = function () {
            return Composer::make(
                base_path(),
                config('microscope.ignored_namespaces', []),
                config('microscope.additional_composer_paths', [])
            );
        };

        PhpFinder::$basePath = base_path();

        [$major] = explode('.', app()->version());

        $color = (int) $major >= 8 ? 'gray' : 'blue';

        config()->set('microscope.colors.line_separator', $color);

        $this->registerCompiler();

        $this->loadConfig();

        app()->singleton(ErrorPrinter::class, function () {
            return ErrorPrinter::singleton();
        });
        Features\CheckRoutes\Installer::spyRouter();

        // We need to start spying before the boot process starts.
        $command = $_SERVER['argv'][1] ?? '';
        // We spy the router in order to have a list of route files.
        $checkAll = Str::startsWith('check:all', $command);
        ($checkAll || Str::startsWith('check:eve', $command)) && Installer::spyEvents();
        ($checkAll || Str::startsWith('check:routes', $command)) && app('router')->spyRouteConflict();
        Str::startsWith('check:action_comment', $command) && app('router')->spyRouteConflict();
        ($checkAll || Str::startsWith('check:gates', $command)) && SpyGate::start();
    }

    private function loadConfig()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'microscope');
    }

    private function canRun()
    {
        if (! $this->app->runningInConsole()) {
            return false;
        }

        if (windows_os()) {
            return true;
        }

        return config('microscope.is_enabled', true) && app()['env'] !== 'production';
    }

    private function registerCompiler()
    {
        $this->app->singleton('microscope.blade.compiler', function () {
            return new SpyBladeCompiler($this->app['files'], $this->app['config']['view.compiled']);
        });
    }

    private function resetCountersOnFinish()
    {
        Event::listen('microscope.finished.checks', function () {
            CheckView::$checkedCallsCount = 0;
            CheckView::$skippedCallsCount = 0;
            ImportsAnalyzer::$checkedRefCount = 0;
            Iterators\ChecksOnPsr4Classes::$checkedFilesCount = 0;
        });
    }
}
