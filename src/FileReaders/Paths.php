<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Imanghafoori\LaravelMicroscope\Iterators\FiltersFiles;

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
                yield $dir => self::filterFiles(PhpFinder::getPathsInDir($dir, $fileName), $folder);
            }
        }
    }
}
