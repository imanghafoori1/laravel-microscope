<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Iterators\FiltersFiles;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;

class Paths
{
    use FiltersFiles;

    /**
     * @param  string|string[]|\Generator  $dirs
     * @param  PathFilterDTO  $pathFilter
     * @return array<string, \Generator<int, \Symfony\Component\Finder\SplFileInfo>>
     */
    public static function getAbsFilePaths($dirs, PathFilterDTO $pathFilter)
    {
        if (! $dirs) {
            return [];
        }

        $fileName = $pathFilter->includeFile;
        is_string($dirs) && ($dirs = [$dirs]);

        return Loop::mapIf(
            $dirs,
            fn ($dir) => is_dir($dir),
            fn ($dir) => self::filterFiles(PhpFinder::getPathsInDir($dir, $fileName), $pathFilter)
        );
    }
}
