<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Imanghafoori\LaravelMicroscope\Iterators\FiltersFiles;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;

class Paths
{
    use FiltersFiles;

    /**
     * @param  string|string[]|\Generator  $dirs
     * @param  PathFilterDTO  $pathFilter
     * @return array|\Generator
     */
    public static function getAbsFilePaths($dirs, PathFilterDTO $pathFilter)
    {
        if (! $dirs) {
            return [];
        }
        $fileName = $pathFilter->includeFile;

        is_string($dirs) && ($dirs = [$dirs]);
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                yield $dir => self::filterFiles(PhpFinder::getPathsInDir($dir, $fileName), $pathFilter);
            }
        }
    }
}
