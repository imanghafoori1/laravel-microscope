<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use ImanGhafoori\ComposerJson\ComposerJson as Composer;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckEvents\Installer;
use Imanghafoori\LaravelMicroscope\Features\CheckUnusedBladeVars\UnusedVarsInstaller;
use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\LaravelMicroscope\ServiceProvider\CommandsRegistry;
use Imanghafoori\LaravelMicroscope\SpyClasses\SpyBladeCompiler;
use Imanghafoori\LaravelMicroscope\SpyClasses\SpyGate;
use Imanghafoori\TokenAnalyzer\Str;
use Symfony\Component\Console\Terminal;

class LaravelMicroscopeServiceProvider extends ServiceProvider
{
    use CommandsRegistry;

    public function boot()
    {
        if (! $this->canRun()) {
            return;
        }

        UnusedVarsInstaller::spyView();

        $this->addCacheStore();

        $this->loadViewsFrom(__DIR__.'/../templates', 'microscope_package');

        Event::listen('microscope.start.command', function () {
            ! defined('microscope_start') && define('microscope_start', microtime(true));
        });

        $this->registerCommands();

        ErrorPrinter::$ignored = config('microscope.ignore');

        $this->publishes([
            __DIR__.'/../templates' => base_path('resources/views/vendor/microscope'),
        ], 'microscope');

        $ds = DIRECTORY_SEPARATOR;

        LaravelPaths::$configPath = array_merge([config_path()], config('microscope.additional_config_paths', []));
        CachedFiles::$folderPath = storage_path('framework'.$ds.'cache'.$ds.'microscope'.$ds);
        BasePath::$path = base_path();
        ErrorPrinter::$terminalWidth = (new Terminal())->getWidth();
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

        app()->singleton(ErrorPrinter::class, fn () => ErrorPrinter::singleton());
        Features\CheckRoutes\Installer::spyRouter();

        // We need to start spying before the boot process starts.
        $command = $_SERVER['argv'][1] ?? '';
        // We spy the router in order to have a list of route files.
        $checkAll = Str::startsWith($command, 'check:all');
        ($checkAll || Str::startsWith($command, 'check:eve')) && Installer::spyEvents();
        ($checkAll || Str::startsWith($command, 'check:rout')) && app('router')->spyRouteConflict();
        Str::startsWith($command, 'check:actio') && app('router')->spyRouteConflict();
        ($checkAll || Str::startsWith($command, 'check:gat')) && SpyGate::start();
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
    }

    private function addCacheStore()
    {
        config()->set('cache.stores.|-microscope-|', [
            'driver' => 'file',
            'path' => storage_path('framework'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'microscope'),
        ]);
    }
}
