<?php

namespace Imanghafoori\LaravelMicroscope\LaravelPaths;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;

class MigrationPaths
{
    public static function get()
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
