<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\BladeFiles;

use Imanghafoori\LaravelMicroscope\Features\CheckUnusedBladeVars\ViewsData;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\FiltersFiles;
use Symfony\Component\Finder\Finder;

class CheckBladePaths
{
    use FiltersFiles;

    public static $scanned = [];

    public static $readOnly = true;

    /**
     * @param  \Generator  $dirs
     * @param  array  $checkers
     * @param  string  $includeFile
     * @param  string  $includeFolder
     * @param  array|callable  $params
     * @return \Generator
     */
    public static function checkPaths($dirs, $checkers, $includeFile, $includeFolder, $params)
    {
        foreach (self::filterUnwantedBlades($dirs) as $dirPath) {
            $finder = self::findFiles($dirPath, $includeFile);
            $filteredFiles = self::filterFiles($finder, $includeFolder);
            $count = self::applyChecks($filteredFiles, $params, $checkers);

            yield $dirPath => $count;
        }
    }

    /**
     * @param  string  $path
     * @param  null  $fileName
     * @return \Symfony\Component\Finder\Finder
     */
    public static function findFiles($path, $fileName = null): Finder
    {
        return Finder::create()
            ->name(($fileName ?: '*').'.blade.php')
            ->files()
            ->in($path);
    }

    /**
     * @param  string  $path
     * @return bool
     */
    private static function shouldSkip($path)
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
     * @param  callable|array  $paramsProvider
     * @param  $checkers
     * @return int
     */
    private static function applyChecks($files, $paramsProvider, $checkers): int
    {
        $count = 0;
        foreach ($files as $blade) {
            $count++;
            /**
             * @var \Symfony\Component\Finder\SplFileInfo $blade
             */
            $absFilePath = $blade->getPathname();

            $file = PhpFileDescriptor::make($absFilePath);
            if (self::$readOnly) {
                $file->setTokenizer(function ($absPath) {
                    return ViewsData::getBladeTokens($absPath);
                });
            }

            $params1 = (! is_array($paramsProvider) && is_callable($paramsProvider)) ? $paramsProvider($file) : $paramsProvider;

            foreach ($checkers as $check) {
                $check::check($file, $params1);
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
