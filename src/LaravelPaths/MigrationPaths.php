<?php

namespace Imanghafoori\LaravelMicroscope\LaravelPaths;

use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;

class MigrationPaths
{
    public static function get()
    {
        // normalize the migration paths
        $migrationDirs = [];

        foreach (app('migrator')->paths() as $path) {
            $migrationDirs[] = FilePath::normalize($path);
        }

        return $migrationDirs;
    }
}
