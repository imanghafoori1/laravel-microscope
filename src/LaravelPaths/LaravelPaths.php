<?php

namespace Imanghafoori\LaravelMicroscope\LaravelPaths;

use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\Iterators\BladeFiles\CheckBladePaths;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use Imanghafoori\TokenAnalyzer\Str;
use JetBrains\PhpStorm\Pure;

class LaravelPaths
{
    public static $configPath = [];

    public static $migrationDirs = [];

    public static $defaultPath = '';

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
     * @return string[]
     */
    public static function configDirs()
    {
        return self::$configPath;
    }

    /**
     * @return \Generator<int, string>
     */
    public static function migrationDirs()
    {
        // normalize the migration paths
        foreach (self::$migrationDirs as $path) {
            if (! is_dir($path)) {
                continue;
            }
            // Excludes the migrations within "vendor" folder:
            if (! Str::startsWith($path, [BasePath::$path.DIRECTORY_SEPARATOR.'vendor'])) {
                yield FilePath::normalize($path);
            }
        }

        yield self::$defaultPath;
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
        return self::migrationDirs();
    }
}
