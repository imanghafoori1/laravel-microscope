<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\BladeFiles;

use Imanghafoori\LaravelMicroscope\Features\CheckUnusedBladeVars\ViewsData;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\FiltersFiles;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Symfony\Component\Finder\Finder;

class CheckBladePaths
{
    use FiltersFiles;

    public static $scanned = [];

    public static $readOnly = true;

    /**
     * @param  \Generator<int, string>  $dirs
     * @param  array<int, class-string>  $checkers
     * @param  PathFilterDTO  $pathDTO
     * @param  array|callable  $params
     * @return \Generator<string, int>
     */
    public static function checkPaths($dirs, $checkers, $params, $pathDTO)
    {
        $includeFile = $pathDTO->includeFile;

        foreach (self::filterUnwantedBlades($dirs) as $dirPath) {
            $finder = self::findFiles($dirPath, $includeFile);
            $filteredFiles = self::filterFiles($finder, $pathDTO);
            $count = self::applyChecks($filteredFiles, $params, $checkers);
            if ($count > 0) {
                yield $dirPath => $count;
            }
        }
    }

    /**
     * @param  string  $path
     * @param  string|null  $fileName
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
     * @param  \Generator<int, \Symfony\Component\Finder\SplFileInfo>  $files
     * @param  callable|array  $paramsProvider
     * @param  array<int, class-string> $checkers
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

            Loop::map($checkers, fn ($check) => $check::check($file, $params1));
        }

        return $count;
    }

    /**
     * @param  \Generator<int, string>  $paths
     * @return \Generator<int, string>
     */
    private static function filterUnwantedBlades($paths)
    {
        return self::filterItems($paths, fn ($path) => ! self::shouldSkip($path));
    }
}
