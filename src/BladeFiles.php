<?php

namespace Imanghafoori\LaravelMicroscope;

use Generator;
use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\SpyClasses\ViewsData;
use Symfony\Component\Finder\Finder;

class BladeFiles
{
    public static $scanned = [];

    /**
     * @param  $checkers
     * @param  $params
     * @param  $fileName
     * @param  $folder
     * @return array<string, int>
     */
    public static function check($checkers, $params = [], $fileName = '', $folder = '')
    {
        self::withoutComponentTags();

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
    private static function checkPaths($paths, $checkers, $fileName, $folder, $params)
    {
        $stats = [];

        foreach (self::filterPaths($paths) as $path) {
            $files = self::findBladeFiles($path);
            $files = self::filterFiles($files, $fileName, $folder);
            $count = self::applyChecks($files, $params, $checkers);

            $stats[$path] = $count;
        }

        return $stats;
    }

    /**
     * @param string $path
     * @return bool
     */
    private static function shouldSkip(string $path)
    {
        if (! is_dir($path)) {
            return true;
        }

        if (strpos($path, base_path('vendor')) !== false) {
            return true;
        }

        // Avoid duplicate scans
        if (in_array($path, self::$scanned)) {
            return true;
        }

        self::$scanned[] = $path;

        return false;
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

    private static function filterItems(array $items, \Closure $condition)
    {
        foreach ($items as $item) {
            if ($condition($item)) {
                yield $item;
            }
        }
    }

    /**
     * @param \Symfony\Component\Finder\Finder $files
     * @param string $fileName
     * @param string $folder
     * @return \Generator
     */
    private static function filterFiles(Finder $files, $fileName, $folder)
    {
        return self::filterItems($files, function ($file) use ($fileName, $folder) {
            return FilePath::contains($file->getPathname(), $fileName, $folder);
        });
    }

    /**
     * @param array $paths
     * @return \Generator
     */
    private static function filterPaths(array $paths)
    {
        return self::filterItems($paths, function ($path) {
            return ! self::shouldSkip($path);
        });
    }

    private static function applyChecks(Generator $files, $params, $checkers): int
    {
        $count = 0;
        foreach ($files as $blade) {
            $count++;
            /**
             * @var \Symfony\Component\Finder\SplFileInfo $blade
             */
            $absPath = $blade->getPathname();
            $tokens = ViewsData::getBladeTokens($absPath);
            $params1 = (! is_array($params) && is_callable($params)) ? $params($tokens, $absPath) : $params;

            foreach ($checkers as $checkerClass) {
                call_user_func_array([$checkerClass, 'check'], [$tokens, $absPath, $params1]);
            }
        }

        return $count;
    }

    private static function findBladeFiles($path): Finder
    {
        return (new Finder)->name('*.blade.php')->files()->in($path);
    }

    private static function withoutComponentTags()
    {
        $compiler = app('microscope.blade.compiler');
        method_exists($compiler, 'withoutComponentTags') && $compiler->withoutComponentTags();
    }
}
