<?php

namespace Imanghafoori\LaravelMicroscope\LaravelPaths;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Iterators\BladeFiles;
use Throwable;

class LaravelPaths
{
    /**
     * @return \Generator
     */
    public static function configDirs()
    {
        yield from array_merge([config_path()], config('microscope.additional_config_paths', []));
    }

    /**
     * @return string|null
     */
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

    /**
     * @return \Generator
     */
    public static function migrationDirs()
    {
        // normalize the migration paths
        foreach (app('migrator')->paths() as $path) {
            if (! is_dir($path)) {
                continue;
            }
            // Excludes the migrations within "vendor" folder:
            if (! Str::startsWith($path, [base_path('vendor')])) {
                yield FilePath::normalize($path);
            }
        }

        yield app()->databasePath('migrations');
    }

    /**
     * @return \Generator
     */
    public static function allBladeFiles()
    {
        foreach (BladeFiles::getViews() as $paths) {
            foreach ($paths as $path) {
                $files = is_dir($path) ? BladeFiles\CheckBladePaths::findFiles($path) : [];
                foreach ($files as $blade) {
                    /**
                     * @var \Symfony\Component\Finder\SplFileInfo $blade
                     */
                    yield $blade->getRealPath();
                }
            }
        }
    }
}
