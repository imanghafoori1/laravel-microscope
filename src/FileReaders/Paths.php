<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Exception;
use Symfony\Component\Finder\Finder;

class Paths
{
    public static function getAbsFilePaths($dirs, $file = null, $folder = null)
    {
        if (! $dirs) {
            return [];
        }

        $folder && ($folder = str_replace('\\', '/', $folder));
        $paths = [];
        foreach ((array) $dirs as $dir) {
            $paths[$dir] = self::getPathsInDir($dir, $file, $folder);
        }

        return $paths;
    }

    private static function getPathsInDir($dir, $file, $folder): array
    {
        try {
            $files = Finder::create()->files()->name('*.php')->in($dir);
            $paths = [];
            foreach ($files as $absFilePath => $f) {
                if (FilePath::contains($absFilePath, $file, $folder)) {
                    $paths[] = $absFilePath;
                }
            }

            return $paths;
        } catch (Exception $e) {
            return [];
        }
    }
}
