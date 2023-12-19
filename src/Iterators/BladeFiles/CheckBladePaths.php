<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\BladeFiles;

use Imanghafoori\LaravelMicroscope\Iterators\FiltersFiles;
use Imanghafoori\LaravelMicroscope\SpyClasses\ViewsData;
use Symfony\Component\Finder\Finder;

class CheckBladePaths
{
    use FiltersFiles;

    public static $scanned = [];

    /**
     * @param  \Generator  $paths
     * @param  array  $checkers
     * @param  string  $includeFile
     * @param  string  $includeFolder
     * @param  array|callable  $params
     * @return \Generator
     */
    public static function checkPaths($paths, $checkers, $includeFile, $includeFolder, $params)
    {
        foreach (self::filterUnwantedBlades($paths) as $path) {
            $files = self::findFiles($path, $includeFile, $includeFolder);
            $count = self::applyChecks($files, $params, $checkers);

            yield $path => $count;
        }
    }

    /**
     * @param  string  $path
     * @return \IteratorAggregate
     */
    public static function findFiles($path, $fileName = null, $folderName = null): Finder
    {
        $finder = Finder::create()
            ->name(($fileName ?: '*').'.blade.php')
            ->files()
            ->in($path);

        $folderName && $finder->path($folderName);

        return $finder;
    }

    /**
     * @param  string  $path
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
     * @param  \Iterator  $files
     * @param  callable|array  $params
     * @param  $checkers
     * @return int
     */
    private static function applyChecks($files, $params, $checkers): int
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
     * @param  array  $paths
     * @return \Generator
     */
    private static function filterUnwantedBlades($paths)
    {
        return self::filterItems($paths, function ($path) {
            return ! self::shouldSkip($path);
        });
    }
}
