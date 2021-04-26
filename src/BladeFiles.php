<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\SpyClasses\ViewsData;
use Symfony\Component\Finder\Finder;

class BladeFiles
{
    public static $checkedFilesNum = 0;

    public static function check($checkers)
    {
        $compiler = app('microscope.blade.compiler');
        method_exists($compiler, 'withoutComponentTags') && $compiler->withoutComponentTags();

        $hints = self::getNamespacedPaths();
        $hints['random_key_69471'] = View::getFinder()->getPaths();

        foreach ($hints as $paths) {
            self::checkPaths($paths, $checkers);
        }
    }

    private static function getNamespacedPaths()
    {
        $hints = View::getFinder()->getHints();
        unset($hints['notifications'], $hints['pagination']);

        return $hints;
    }

    public static function checkPaths($paths, $checkers)
    {
        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }
            $files = (new Finder())->name('*.blade.php')->files()->in($path);

            foreach ($files as $blade) {
                self::$checkedFilesNum++;
                /**
                 * @var \Symfony\Component\Finder\SplFileInfo $blade
                 */
                $tokens = ViewsData::getBladeTokens($blade->getPathname());
                foreach ($checkers as $checkerClass) {
                    call_user_func_array([$checkerClass, 'check'], [$tokens, $blade->getPathname()]);
                }
            }
        }
    }
}
