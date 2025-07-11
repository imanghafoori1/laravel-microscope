<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\Check;

class BladeFiles implements Check
{
    /**
     * @param  $checkers
     * @param  $params
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return \Generator<string, int>
     */
    public static function check($checkers, $params = [], $pathDTO = null)
    {
        self::withoutComponentTags();

        foreach (self::getViews() as $paths) {
            yield from BladeFiles\CheckBladePaths::checkPaths($paths, $checkers, $pathDTO, $params);
        }
    }

    /**
     * @return \Generator<string, array>
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
