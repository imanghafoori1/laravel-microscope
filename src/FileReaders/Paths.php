<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Exception;
use Imanghafoori\LaravelMicroscope\Iterators\FiltersFiles;
use Symfony\Component\Finder\Finder;

class Paths
{
    use FiltersFiles;
    /**
     * @param  $dirs
     * @param  null|string  $includeFileName
     * @param  null|string  $includeFolder
     * @return \iterable
     */
    public static function getAbsFilePaths($dirs, $includeFileName = null, $includeFolder = null)
    {
        if (! $dirs) {
            return [];
        }

        $includeFolder && ($includeFolder = str_replace('\\', '/', $includeFolder));
        is_string($dirs) && ($dirs = [$dirs]);
        foreach ($dirs as $dir) {
            yield $dir => self::filterFiles(self::getPathsInDir($dir, $includeFileName), $includeFolder);
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
            return Finder::create();
        }
    }
}
