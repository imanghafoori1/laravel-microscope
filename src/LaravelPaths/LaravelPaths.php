<?php

namespace Imanghafoori\LaravelMicroscope\LaravelPaths;

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
        if (! is_dir($dir)) {
            $dir = app()->databasePath('seeders');
        }

        return is_dir($dir) ? $dir : null;
    }

    public static function factoryDirs()
    {
        try {
            return app()->make('Illuminate\Database\Eloquent\Factory')->loadedPaths;
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

        $migrationDirs[] = app()->databasePath('migrations');

        return $migrationDirs;
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
}
