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
use Imanghafoori\LaravelMicroscope\FileReaders\PhpFinder;
use Imanghafoori\LaravelMicroscope\Foundations\Path;
use Imanghafoori\LaravelMicroscope\ServiceProvider\CommandsRegistry;
use Imanghafoori\LaravelMicroscope\SpyClasses\SpyBladeCompiler;
use Imanghafoori\LaravelMicroscope\SpyClasses\SpyGate;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class LaravelMicroscopeServiceProvider extends ServiceProvider
{
    use CommandsRegistry;

    public function boot()
    {
        UnusedVarsInstaller::spyView();

        if (! $this->canRun()) {
            return;
        }

        $this->addCacheStore();

        $this->loadViewsFrom(__DIR__.'/../templates', 'microscope_package');

        Event::listen('microscope.start.command', function () {
            ! defined('microscope_start') && define('microscope_start', microtime(true));
        });

        $this->resetCountersOnFinish();

        $this->registerCommands();

        ErrorPrinter::$ignored = config('microscope.ignore');

        $this->publishes([
            __DIR__.'/../templates' => base_path('resources/views/vendor/microscope'),
        ], 'microscope');

        ConsolePrinterInstaller::boot();
    }

    public function register()
    {
        if (! $this->canRun()) {
            return;
        }

        $this->setBasePath();
        $this->setLineSeparatorColor();
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
        $configPath = __DIR__.'/../config/config.php';

        $this->mergeConfigFrom($configPath, 'microscope');
        $this->publishes([$configPath => config_path('microscope.php')], 'config');
    }

    private function canRun()
    {
        if (! $this->app->runningInConsole()) {
            return false;
        }

        if (! config('microscope.is_enabled', true)) {
            return false;
        }

        if (windows_os()) {
            return true;
        }

        return app()['env'] !== 'production';
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
        });

        Event::listen('microscope.finished.checks', function () {
            ImportsAnalyzer::$checkedRefCount = 0;
            Iterators\ChecksOnPsr4Classes::$checkedFilesCount = 0;
        });
    }

    private function setLineSeparatorColor()
    {
        [$major] = explode('.', app()->version());
        $color = (int) $major >= 9 ? 'gray' : 'blue';
        config()->set('microscope.colors.line_separator', $color);
    }

    private function setBasePath()
    {
        ComposerJson::$composer = function () {
            return Composer::make(base_path(), config('microscope.ignored_namespaces', []), config('microscope.additional_composer_paths', []));
        };

        PhpFinder::$basePath = base_path();
        Path::setBasePath(base_path());
    }

    private function addCacheStore()
    {
        config()->set('cache.stores.|-microscope-|', [
            'driver' => 'file',
            'path' => storage_path('framework'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'microscope'),
        ]);
    }
}
