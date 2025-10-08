<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\BladeFiles;

use Imanghafoori\LaravelMicroscope\Features\CheckUnusedBladeVars\ViewsData;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\FiltersFiles;
use Symfony\Component\Finder\Finder;

class CheckBladePaths
{
    use FiltersFiles;

    public static $scanned = [];

    public static $readOnly = true;

    /**
     * @param \Generator<int, string> $dirs
     * @param $checker
     * @return \Generator<string, int>
     */
    public static function checkPaths($dirs, CheckSet $checker)
    {
        $includeFile = $checker->pathDTO->includeFile;

        foreach (self::filterUnwantedBlades($dirs) as $dirPath) {
            $finder = self::findFiles($dirPath, $includeFile);
            $filteredFiles = self::filterFiles($finder, $checker->pathDTO);
            $count = self::applyChecks($filteredFiles, $checker->checks);
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
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\CheckCollection  $checks
     * @return int
     */
    private static function applyChecks($files, $checks): int
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
                $file->setTokenizer(fn ($absPath) => ViewsData::getBladeTokens($absPath));
            }

            $checks->applyOnFile($file);
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
