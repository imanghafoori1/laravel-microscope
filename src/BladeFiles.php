<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\SpyClasses\ViewsData;
use Symfony\Component\Finder\Finder;

class BladeFiles
{
    public static $checkedFilesCount = 0;

    /**
     * @param  $checkers
     * @param  $params
     * @param  $fileName
     * @param  $folder
     * @return array<string, int>
     */
    public static function check($checkers, $params = [], $fileName = '', $folder = '')
    {
        $compiler = app('microscope.blade.compiler');
        method_exists($compiler, 'withoutComponentTags') && $compiler->withoutComponentTags();

        $stats = [];
        foreach (self::getViews() as $paths) {
            $stats = array_merge($stats, self::checkPaths($paths, $checkers, $fileName, $folder, $params));
        }

        return $stats;
    }

    /**
     * @return array<string, string>
     */
    private static function getNamespacedPaths()
    {
        $hints = View::getFinder()->getHints();
        unset($hints['notifications'], $hints['pagination']);

        return $hints;
    }

    /**
     * @param  string[]  $paths
     * @param  $checkers
     * @param  $fileName
     * @param  $folder
     * @param  $params
     * @return array<string, int>
     */
    public static function checkPaths($paths, $checkers, $fileName, $folder, $params)
    {
        $stats = [];
        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }
            if (strpos($path, base_path('vendor')) !== false) {
                continue;
            }
            $files = (new Finder)->name('*.blade.php')->files()->in($path);
            $count = 0;

            foreach ($files as $blade) {
                /**
                 * @var \Symfony\Component\Finder\SplFileInfo $blade
                 */
                $absPath = $blade->getPathname();

                if (! FilePath::contains($absPath, $fileName, $folder)) {
                    continue;
                }

                $count++;
                self::$checkedFilesCount++;
                $tokens = ViewsData::getBladeTokens($absPath);
                $params1 = (! is_array($params) && is_callable($params)) ? $params($tokens, $absPath) : $params;

                foreach ($checkers as $checkerClass) {
                    call_user_func_array([$checkerClass, 'check'], [$tokens, $absPath, $params1]);
                }
            }

            $stats[$path] = $count;
        }

        return $stats;
    }

    /**
     * @return array<string, string>
     */
    private static function getViews()
    {
        $hints = self::getNamespacedPaths();
        $hints['random_key_69471'] = View::getFinder()->getPaths();

        return $hints;
    }
}
