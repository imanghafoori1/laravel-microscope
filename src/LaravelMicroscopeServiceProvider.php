<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\View;
use ImanGhafoori\ComposerJson\ComposerJson as Composer;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ConsolePrinterInstaller;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckEvents\Installer;
use Imanghafoori\LaravelMicroscope\Features\CheckView\Check\CheckView;
use Imanghafoori\LaravelMicroscope\Features\ListModels\ListModelsArtisanCommand;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\SpyClasses\SpyBladeCompiler;
use Imanghafoori\LaravelMicroscope\SpyClasses\SpyGate;
use Imanghafoori\LaravelMicroscope\SpyClasses\ViewsData;
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
        Commands\CheckBadPractice::class,
        Commands\CheckExtractBladeIncludes::class,
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
        $this->shouldSpyViews() && $this->spyView();

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

        FilePath::$basePath = base_path();

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
        // ($checkAll || Str::startsWith('check:events', $command)) && $this->spyEvents();
        ($checkAll || Str::startsWith('check:gates', $command)) && $this->spyGates();
    }

    private function spyGates()
    {
        $this->app->singleton(GateContract::class, function ($app) {
            return new SpyGate($app, function () use ($app) {
                return call_user_func($app['auth']->userResolver());
            });
        });
    }

    public function spyView()
    {
        app()->singleton('microscope.views', ViewsData::class);

        \View::creator('*', function (View $view) {
            resolve('microscope.views')->add($view);
        });

        app()->terminating(function () {
            $spy = resolve('microscope.views');
            if (! $spy->main || Str::startsWith($spy->main->getName(), ['errors::'])) {
                return;
            }

            $action = $this->getActionName();

            $uselessVars = array_keys(array_diff_key($spy->getMainVars(), $spy->readTokenizedVars()));
            $viewName = $spy->main->getName();

            $uselessVars && $this->logUnusedViewVars($viewName, $action, $uselessVars);
        });
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

    public function getActionName()
    {
        $cRoute = \Route::getCurrentRoute();

        return $cRoute ? $cRoute->getActionName() : '';
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

    private function logUnusedViewVars($viewName, string $action, array $uselessVars)
    {
        Log::info('Laravel Microscope - The view file "'.$viewName.'"');
        Log::info('At "'.$action.'" has some unused variables passed to it: ');
        Log::info($uselessVars);
        Log::info('If you do not see these variables passed in a controller, look in view composers.');
    }

    private function shouldSpyViews()
    {
        return (app()['env'] !== 'production') && config('microscope.log_unused_view_vars', false);
    }
}
