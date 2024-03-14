<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckUnusedBladeVars;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UnusedVarsInstaller
{
    public static function spyView()
    {
        if (! self::shouldSpyViews()) {
            return;
        }

        app()->singleton('microscope.views', ViewsData::class);

        \View::creator('*', function (View $view) {
            resolve('microscope.views')->add($view);
        });

        app()->terminating(function () {
            /**
             * @var $spy  ViewsData
             */
            $spy = resolve('microscope.views');
            if (! $spy->main || Str::startsWith($spy->main->getName(), ['errors::'])) {
                return;
            }

            $action = self::getActionName();

            $uselessVars = array_keys(array_diff_key($spy->getMainVars(), $spy->readTokenizedVars()));
            $viewName = $spy->main->getName();

            $uselessVars && self::logUnusedViewVars($viewName, $action, $uselessVars);
        });
    }

    private static function getActionName()
    {
        $cRoute = \Route::getCurrentRoute();

        return $cRoute ? $cRoute->getActionName() : '';
    }

    private static function shouldSpyViews()
    {
        return (app()['env'] !== 'production') && config('microscope.log_unused_view_vars', false);
    }

    private static function logUnusedViewVars($viewName, string $action, array $uselessVars)
    {
        Log::info('Laravel Microscope - The view file "'.$viewName.'"');
        Log::info('At "'.$action.'" has some unused variables passed to it: ');
        Log::info($uselessVars);
        Log::info('If you do not see these variables passed in a controller, look in view composers.');
    }
}
