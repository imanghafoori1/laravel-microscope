<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Illuminate\Support\Facades\View;

class BladeFiles
{
    /**
     * @param  $checkers
     * @param  $params
     * @param  string  $includeFileName
     * @param  string  $includeFolder
     * @return \Generator<string, int>
     */
    public static function check($checkers, $params = [], $includeFileName = null, $includeFolder = null)
    {
        self::withoutComponentTags();

        foreach (self::getViews() as $paths) {
            yield from BladeFiles\CheckBladePaths::checkPaths($paths, $checkers, $includeFileName, $includeFolder, $params);
        }
    }

    /**
     * @return \Generator<string, string>
     */
    public static function getViews()
    {
        $hints = View::getFinder()->getHints();
        $hints['random_key_69471'] = View::getFinder()->getPaths();
        unset(
            $hints['notifications'],
            $hints['pagination']
        );

        yield from $hints;
    }

    /**
     * @return void
     */
    private static function withoutComponentTags()
    {
        $compiler = app('microscope.blade.compiler');
        method_exists($compiler, 'withoutComponentTags') && $compiler->withoutComponentTags();
    }
}
