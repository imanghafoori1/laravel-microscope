<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Illuminate\Support\Facades\View;

class BladeFiles
{
    /**
     * @param  $checkers
     * @param  $params
     * @param  string  $fileName
     * @param  string  $folder
     * @return array<string, int>
     */
    public static function check($checkers, $params = [], $fileName = '', $folder = '')
    {
        self::withoutComponentTags();

        foreach (self::getViews() as $paths) {
            yield from BladeFiles\CheckBladePaths::checkPaths($paths, $checkers, $fileName, $folder, $params);
        }
    }

    /**
     * @return array<string, string>
     */
    public static function getViews()
    {
        $hints = View::getFinder()->getHints();
        $hints['random_key_69471'] = View::getFinder()->getPaths();
        unset(
            $hints['notifications'],
            $hints['pagination']
        );

        return $hints;
    }

    private static function withoutComponentTags()
    {
        $compiler = app('microscope.blade.compiler');
        method_exists($compiler, 'withoutComponentTags') && $compiler->withoutComponentTags();
    }
}
