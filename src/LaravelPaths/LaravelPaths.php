<?php

namespace Imanghafoori\LaravelMicroscope\LaravelPaths;

use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;

class LaravelPaths
{
    public static function factoryDirs()
    {
        return app()->make(Factory::class)->loadedPaths;
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
}
