<?php

namespace Imanghafoori\LaravelMicroscope\LaravelPaths;

use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;
use Symfony\Component\Finder\Finder;
use Throwable;

class LaravelPaths
{
    public static function seedersDir()
    {
        $dir = app()->databasePath('seeds');

        return is_dir($dir) ? $dir : null;
    }

    public static function factoryDirs()
    {
        try {
            return app()->make(Factory::class)->loadedPaths;
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function migrationDirs()
    {
        // normalize the migration paths
        $migrationDirs = [];

        foreach (app('migrator')->paths() as $path) {
            // Excludes the migrations within "vendor" folder:
            if (! Str::startsWith($path, [base_path('vendor')])) {
                $migrationDirs[] = FilePath::normalize($path);
            }
        }

        $migrationDirs[] = app()->databasePath().DIRECTORY_SEPARATOR.'migrations';

        return $migrationDirs;
    }

    /**
     * Check given path should be ignored.
     *
     * @param  string  $path
     * @return bool
     */
    public static function isIgnored($path)
    {
        $ignorePatterns = config('microscope.ignore');

        if (! $ignorePatterns || ! is_array($ignorePatterns)) {
            return false;
        }

        foreach ($ignorePatterns as $ignorePattern) {
            if (Str::is(base_path($ignorePattern), $path)) {
                return true;
            }
        }

        return false;
    }

    public static function bladeFilePaths()
    {
        $bladeFiles = [];
        $hints = self::getNamespacedPaths();
        $hints['1'] = View::getFinder()->getPaths();

        foreach ($hints as $paths) {
            foreach ($paths as $path) {
                $files = is_dir($path) ? Finder::create()->name('*.blade.php')->files()->in($path) : [];
                foreach ($files as $blade) {
                    /**
                     * @var \Symfony\Component\Finder\SplFileInfo $blade
                     */
                    $bladeFiles[] = $blade->getRealPath();
                }
            }
        }

        return $bladeFiles;
    }

    private static function getNamespacedPaths()
    {
        $hints = View::getFinder()->getHints();
        unset($hints['notifications'], $hints['pagination']);

        return $hints;
    }

    public static function collectNonPsr4Paths()
    {
        $paths = [
            RoutePaths::get(),
            Paths::getAbsFilePaths(LaravelPaths::migrationDirs()),
            Paths::getAbsFilePaths(config_path()),
            Paths::getAbsFilePaths(LaravelPaths::factoryDirs()),
            Paths::getAbsFilePaths(LaravelPaths::seedersDir()),
            LaravelPaths::bladeFilePaths(),
        ];

        return self::mergePaths($paths);
    }

    private static function mergePaths($paths)
    {
        $all = [];
        foreach ($paths as $p) {
            $all = array_merge($all, $p);
        }

        return $all;
    }
}
