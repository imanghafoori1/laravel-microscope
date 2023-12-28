<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Exception;
use Imanghafoori\LaravelMicroscope\Iterators\FiltersFiles;
use Symfony\Component\Finder\Finder;

class Paths
{
    use FiltersFiles;

    /**
     * @param  string|string[]|\Generator  $dirs
     * @param  null|string  $fileName
     * @param  null|string  $folder
     * @return \Traversable
     */
    public static function getAbsFilePaths($dirs, $fileName = null, $folder = null)
    {
        if (! $dirs) {
            return [];
        }

        $folder && ($folder = str_replace('\\', '/', $folder));
        is_string($dirs) && ($dirs = [$dirs]);
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                yield $dir => self::filterFiles(self::getPathsInDir($dir, $fileName), $folder);
            }
        }
    }

    /**
     * @param  $dir
     * @param  $fileName
     * @return \Symfony\Component\Finder\Finder
     */
    private static function getPathsInDir($dir, $fileName)
    {
        try {
            return Finder::create()->files()->name(($fileName ?: '*').'.php')->in($dir);
        } catch (Exception $e) {
            dump($e);
        }
    }
}
