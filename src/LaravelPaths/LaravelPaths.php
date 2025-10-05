<?php

namespace Imanghafoori\LaravelMicroscope\LaravelPaths;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\Iterators\BladeFiles\CheckBladePaths;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use JetBrains\PhpStorm\Pure;
use Throwable;

class LaravelPaths
{
    public static $configPath = [];

    public static $migrationDirs = null;

    /**
     * @return array<string, \Generator<int, string>>
     */
    #[Pure(true)]
    public static function getMigrationConfig()
    {
        return [
            'config' => self::configDirs(),
            'migrations' => self::getMigrationDirs(),
        ];
    }

    /**
     * @return \Generator<int, string>
     */
    public static function configDirs()
    {
        return self::$configPath;
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
     * @return \Generator<int, string>
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

    public static function getMigrationsFiles($pathDTO)
    {
        return Paths::getAbsFilePaths(self::getMigrationDirs(), $pathDTO);
    }

    /**
     * @return \Generator<int, string>
     */
    public static function allBladeFiles()
    {
        foreach (ForBladeFiles::getViewsPaths() as $paths) {
            foreach ($paths as $path) {
                $files = is_dir($path) ? CheckBladePaths::findFiles($path) : [];
                foreach ($files as $blade) {
                    /**
                     * @var \Symfony\Component\Finder\SplFileInfo $blade
                     */
                    yield $blade->getRealPath();
                }
            }
        }
    }

    private static function getMigrationDirs()
    {
        if (self::$migrationDirs) {
            $dirs = (self::$migrationDirs)();
        } else {
            $dirs = self::migrationDirs();
        }

        return $dirs;
    }
}
