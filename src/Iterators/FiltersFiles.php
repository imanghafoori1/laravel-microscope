<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Closure;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Symfony\Component\Finder\Finder;

trait FiltersFiles
{
    /**
     * @param  \Symfony\Component\Finder\Finder  $files
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return \Generator<int, \Symfony\Component\Finder\SplFileInfo>
     */
    private static function filterFiles(Finder $files, $pathDTO)
    {
        return self::filterItems($files, function ($file) use ($pathDTO) {
            return FilePath::contains($file->getPathname(), $pathDTO);
        });
    }

    /**
     * @param  $items
     * @param  \Closure  $condition
     * @return \Generator<int, \Symfony\Component\Finder\SplFileInfo>
     */
    private static function filterItems($items, Closure $condition)
    {
        foreach ($items as $item) {
            if ($condition($item)) {
                yield $item;
            }
        }
    }
}
