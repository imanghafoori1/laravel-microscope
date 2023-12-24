<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\BladeFiles;

use Generator;
use Imanghafoori\LaravelMicroscope\Iterators\FiltersFiles;
use Imanghafoori\LaravelMicroscope\SpyClasses\ViewsData;
use Symfony\Component\Finder\Finder;

class CheckBladePaths
{
    use FiltersFiles;

    public static $scanned = [];

    /**
     * @param  string[]  $paths
     * @param  array  $checkers
     * @param  string  $fileName
     * @param  string  $folder
     * @param  array|callable  $params
     * @return \Generator
     */
    public static function checkPaths($paths, $checkers, $fileName, $folder, $params)
    {
        foreach (self::filterPaths($paths) as $path) {
            $files = self::findFiles($path);
            $files = self::filterFiles($files, $fileName, $folder);
            $count = self::applyChecks($files, $params, $checkers);

            yield $path => $count;
        }
    }

    /**
     * @param  string  $path
     * @return \Symfony\Component\Finder\Finder
     */
    public static function findFiles($path): Finder
    {
        return Finder::create()->name('*.blade.php')->files()->in($path);
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
     * @param  \Generator  $files
     * @param  callable|array  $params
     * @param  $checkers
     * @return int
     */
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
}
