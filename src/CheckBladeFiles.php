<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Facades\View;
use Symfony\Component\Finder\Finder;

class CheckBladeFiles
{
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @param $methods
     *
     * @return void
     */
    public static function applyChecks($methods)
    {
        $hints = self::getNamespacedPaths();
        $hints['1'] = View::getFinder()->getPaths();
        foreach ($hints as $paths) {
            self::checkPaths($paths, $methods);
        }
    }

    private static function getNamespacedPaths()
    {
        $hints = View::getFinder()->getHints();
        unset($hints['notifications'], $hints['pagination']);

        return $hints;
    }

    public static function checkPaths($paths, $methods)
    {
        foreach ($paths as $path) {
            $files = (new Finder)->name('*.blade.php')->files()->in($path);

            foreach ($files as $blade) {
                /**
                 * @var \Symfony\Component\Finder\SplFileInfo $blade
                 */
                $content = $blade->getContents();
                $tokens = token_get_all(app('blade.compiler')->compileString($content));

                foreach ($methods as $method) {
                    call_user_func_array([$method, 'check'], [$tokens, $blade->getPathname()]);
                }
            }
        }
    }
}
