<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Exception;
use Symfony\Component\Finder\Finder;

class Paths
{
    /**
     * @param  $dirs
     * @param  $file
     * @param  $folder
     * @return array<string, array<int, string>>
     */
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

    /**
     * @param  $dir
     * @param  $file
     * @param  $folder
     * @return string[]
     */
    private static function getPathsInDir($dir, $file, $folder)
    {
        try {
            $files = Finder::create()->files()->name('*.php')->in($dir);
            foreach ($files as $absFilePath => $f) {
                if (FilePath::contains($absFilePath, $file, $folder)) {
                    yield $absFilePath;
                }
            }
        } catch (Exception $e) {
            return [];
        }
    }
}
